"""
Tabya NMT (Neural Machine Translation) Model Architecture
Encoder-Decoder Seq2Seq with Bahdanau Attention & GRU
Target Language: English -> Bahasa Daerah Kampung Yenbuba (Biak-Beteu)
"""

import torch
import torch.nn as nn
import torch.nn.functional as F
import random

class Encoder(nn.Module):
    def __init__(self, input_dim, emb_dim, enc_hid_dim, dec_hid_dim, dropout=0.2):
        super().__init__()
        self.embedding = nn.Embedding(input_dim, emb_dim, padding_idx=0)
        self.rnn = nn.GRU(emb_dim, enc_hid_dim, bidirectional=True, batch_first=True)
        self.fc = nn.Linear(enc_hid_dim * 2, dec_hid_dim)
        self.dropout = nn.Dropout(dropout)

    def forward(self, src, src_len=None):
        # src: [batch_size, src_len]
        embedded = self.dropout(self.embedding(src))  # [batch_size, src_len, emb_dim]
        outputs, hidden = self.rnn(embedded)          # outputs: [batch, len, 2*enc_hid]
                                                      # hidden: [2, batch, enc_hid]
        # hidden[-2, :, :] is forward, hidden[-1, :, :] is backward
        hidden_cat = torch.cat((hidden[-2, :, :], hidden[-1, :, :]), dim=1)
        hidden = torch.tanh(self.fc(hidden_cat))      # [batch_size, dec_hid_dim]
        return outputs, hidden

class Attention(nn.Module):
    def __init__(self, enc_hid_dim, dec_hid_dim):
        super().__init__()
        self.attn = nn.Linear((enc_hid_dim * 2) + dec_hid_dim, dec_hid_dim)
        self.v = nn.Linear(dec_hid_dim, 1, bias=False)

    def forward(self, hidden, encoder_outputs):
        # hidden: [batch_size, dec_hid_dim]
        # encoder_outputs: [batch_size, src_len, enc_hid_dim * 2]
        src_len = encoder_outputs.shape[1]
        hidden_rep = hidden.unsqueeze(1).repeat(1, src_len, 1) # [batch, src_len, dec_hid]
        energy = torch.tanh(self.attn(torch.cat((hidden_rep, encoder_outputs), dim=2)))
        attention = self.v(energy).squeeze(2) # [batch_size, src_len]
        return F.softmax(attention, dim=1)

class Decoder(nn.Module):
    def __init__(self, output_dim, emb_dim, enc_hid_dim, dec_hid_dim, dropout=0.2):
        super().__init__()
        self.output_dim = output_dim
        self.attention = Attention(enc_hid_dim, dec_hid_dim)
        self.embedding = nn.Embedding(output_dim, emb_dim, padding_idx=0)
        self.rnn = nn.GRU((enc_hid_dim * 2) + emb_dim, dec_hid_dim, batch_first=True)
        self.fc_out = nn.Linear((enc_hid_dim * 2) + dec_hid_dim + emb_dim, output_dim)
        self.dropout = nn.Dropout(dropout)

    def forward(self, input_token, hidden, encoder_outputs):
        # input_token: [batch_size]
        # hidden: [batch_size, dec_hid_dim]
        # encoder_outputs: [batch_size, src_len, enc_hid_dim * 2]
        input_token = input_token.unsqueeze(1) # [batch_size, 1]
        embedded = self.dropout(self.embedding(input_token)) # [batch, 1, emb_dim]

        a = self.attention(hidden, encoder_outputs).unsqueeze(1) # [batch, 1, src_len]
        weighted = torch.bmm(a, encoder_outputs) # [batch, 1, enc_hid_dim * 2]

        rnn_input = torch.cat((embedded, weighted), dim=2) # [batch, 1, emb_dim + 2*enc_hid]
        output, hidden = self.rnn(rnn_input, hidden.unsqueeze(0))
        # output: [batch, 1, dec_hid], hidden: [1, batch, dec_hid]

        embedded = embedded.squeeze(1)
        output = output.squeeze(1)
        weighted = weighted.squeeze(1)

        prediction = self.fc_out(torch.cat((output, weighted, embedded), dim=1))
        return prediction, hidden.squeeze(0), a.squeeze(1)

class Seq2Seq(nn.Module):
    def __init__(self, encoder, decoder, device):
        super().__init__()
        self.encoder = encoder
        self.decoder = decoder
        self.device = device

    def forward(self, src, trg, teacher_forcing_ratio=0.5):
        batch_size = src.shape[0]
        trg_len = trg.shape[1]
        trg_vocab_size = self.decoder.output_dim

        outputs = torch.zeros(batch_size, trg_len, trg_vocab_size).to(self.device)
        encoder_outputs, hidden = self.encoder(src)

        # first input to decoder is <sos> token
        input_token = trg[:, 0]

        for t in range(1, trg_len):
            output, hidden, _ = self.decoder(input_token, hidden, encoder_outputs)
            outputs[:, t, :] = output
            teacher_force = random.random() < teacher_forcing_ratio
            top1 = output.argmax(1)
            input_token = trg[:, t] if teacher_force else top1

        return outputs

    def translate_sentence(self, src_tensor, src_vocab, trg_vocab, max_len=25):
        self.eval()
        with torch.no_grad():
            encoder_outputs, hidden = self.encoder(src_tensor)
            idx2word = {int(k): v for k, v in trg_vocab['idx2word'].items()}
            sos_idx = trg_vocab['word2idx']['<sos>']
            eos_idx = trg_vocab['word2idx']['<eos>']

            input_token = torch.tensor([sos_idx], device=self.device)
            translated_tokens = []
            attentions = []

            for _ in range(max_len):
                output, hidden, attn = self.decoder(input_token, hidden, encoder_outputs)
                top1 = output.argmax(1).item()
                attentions.append(attn.cpu().squeeze().tolist())
                if top1 == eos_idx:
                    break
                word = idx2word.get(top1, '<unk>')
                if word not in ['<sos>', '<pad>', '<eos>']:
                    translated_tokens.append(word)
                input_token = torch.tensor([top1], device=self.device)

        return translated_tokens, attentions

