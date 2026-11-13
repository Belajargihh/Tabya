"""
Tabya NMT Model Trainer
Trains PyTorch Seq2Seq RNN-GRU Model on English -> Yenbuba Dataset
"""

import os
import sys
import json
import re
import random
import time
import datetime

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

import torch
import torch.nn as nn
import torch.optim as optim
from model import Encoder, Decoder, Seq2Seq
DATASET_PATH = os.path.join(BASE_DIR, 'dataset.json')
WEIGHTS_PATH = os.path.join(BASE_DIR, 'model_weights.pth')
VOCAB_PATH = os.path.join(BASE_DIR, 'vocab.json')
META_PATH = os.path.join(BASE_DIR, 'training_meta.json')

def tokenize(text):
    text = text.lower().strip()
    text = re.sub(r'[^\w\s]', '', text)
    tokens = text.split()
    return tokens if tokens else ['']

def build_vocab(sentences):
    word2idx = {'<pad>': 0, '<sos>': 1, '<eos>': 2, '<unk>': 3}
    idx2word = {0: '<pad>', 1: '<sos>', 2: '<eos>', 3: '<unk>'}
    idx = 4
    for sent in sentences:
        for word in sent:
            if word and word not in word2idx:
                word2idx[word] = idx
                idx2word[idx] = word
                idx += 1
    return {'word2idx': word2idx, 'idx2word': idx2word}

def encode_sentence(tokens, vocab, add_sos_eos=True):
    w2i = vocab['word2idx']
    unk_idx = w2i['<unk>']
    indices = [w2i.get(t, unk_idx) for t in tokens]
    if add_sos_eos:
        return [w2i['<sos>']] + indices + [w2i['<eos>']]
    return indices

def pad_sequences(batch, pad_idx=0):
    max_len = max(len(seq) for seq in batch)
    padded = [seq + [pad_idx] * (max_len - len(seq)) for seq in batch]
    return torch.tensor(padded, dtype=torch.long)

def main():
    start_time = time.time()
    print("=== Training Tabya NMT Model (Seq2Seq GRU) ===")

    if not os.path.exists(DATASET_PATH):
        print(f"Error: Dataset not found at {DATASET_PATH}")
        sys.exit(1)

    with open(DATASET_PATH, 'r', encoding='utf-8') as f:
        data = json.load(f)

    print(f"Loaded {len(data)} dataset pairs from {DATASET_PATH}")

    src_sentences = []
    trg_sentences = []

    for item in data:
        src_tokens = tokenize(item.get('english_text', ''))
        trg_tokens = tokenize(item.get('yenbuba_text', ''))
        if src_tokens and trg_tokens:
            src_sentences.append(src_tokens)
            trg_sentences.append(trg_tokens)

    # Build Vocabularies
    src_vocab = build_vocab(src_sentences)
    trg_vocab = build_vocab(trg_sentences)

    print(f"English Vocab Size: {len(src_vocab['word2idx'])} tokens")
    print(f"Yenbuba Vocab Size: {len(trg_vocab['word2idx'])} tokens")

    # Hyperparameters
    INPUT_DIM = len(src_vocab['word2idx'])
    OUTPUT_DIM = len(trg_vocab['word2idx'])
    ENC_EMB_DIM = 64
    DEC_EMB_DIM = 64
    ENC_HID_DIM = 128
    DEC_HID_DIM = 128
    ENC_DROPOUT = 0.1
    DEC_DROPOUT = 0.1
    N_EPOCHS = 200
    LR = 0.003
    DEVICE = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

    enc = Encoder(INPUT_DIM, ENC_EMB_DIM, ENC_HID_DIM, DEC_HID_DIM, ENC_DROPOUT)
    dec = Decoder(OUTPUT_DIM, DEC_EMB_DIM, ENC_HID_DIM, DEC_HID_DIM, DEC_DROPOUT)
    model = Seq2Seq(enc, dec, DEVICE).to(DEVICE)

    optimizer = optim.Adam(model.parameters(), lr=LR)
    criterion = nn.CrossEntropyLoss(ignore_index=0)

    # Prepare batches
    encoded_pairs = []
    for s, t in zip(src_sentences, trg_sentences):
        s_enc = encode_sentence(s, src_vocab, add_sos_eos=False)
        t_enc = encode_sentence(t, trg_vocab, add_sos_eos=True)
        encoded_pairs.append((s_enc, t_enc))

    # Training loop
    model.train()
    loss_history = []

    for epoch in range(1, N_EPOCHS + 1):
        random.shuffle(encoded_pairs)
        epoch_loss = 0
        batch_size = 16

        for i in range(0, len(encoded_pairs), batch_size):
            batch = encoded_pairs[i:i + batch_size]
            src_batch = [item[0] for item in batch]
            trg_batch = [item[1] for item in batch]

            src_tensor = pad_sequences(src_batch, pad_idx=0).to(DEVICE)
            trg_tensor = pad_sequences(trg_batch, pad_idx=0).to(DEVICE)

            optimizer.zero_grad()
            output = model(src_tensor, trg_tensor, teacher_forcing_ratio=0.6)

            # output: [batch_size, trg_len, output_dim]
            # trg: [batch_size, trg_len]
            output_dim = output.shape[-1]
            output = output[:, 1:].reshape(-1, output_dim)
            trg = trg_tensor[:, 1:].reshape(-1)

            loss = criterion(output, trg)
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), 1.0)
            optimizer.step()

            epoch_loss += loss.item()

        avg_loss = epoch_loss / max(1, (len(encoded_pairs) // batch_size))
        if epoch % 20 == 0 or epoch == N_EPOCHS:
            loss_history.append({"epoch": epoch, "loss": round(avg_loss, 4)})
            print(f"Epoch {epoch:03d}/{N_EPOCHS} | Loss: {avg_loss:.4f}")

    # Save Model Weights
    torch.save(model.state_dict(), WEIGHTS_PATH)
    print(f"Model saved to {WEIGHTS_PATH}")

    # Save Vocab
    with open(VOCAB_PATH, 'w', encoding='utf-8') as f:
        json.dump({'src_vocab': src_vocab, 'trg_vocab': trg_vocab}, f, indent=2, ensure_ascii=False)
    print(f"Vocab saved to {VOCAB_PATH}")

    duration = round(time.time() - start_time, 2)

    meta = {
        "status": "trained",
        "timestamp": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "duration_seconds": duration,
        "epochs": N_EPOCHS,
        "final_loss": loss_history[-1]['loss'] if loss_history else None,
        "loss_history": loss_history,
        "dataset_count": len(data),
        "src_vocab_size": len(src_vocab['word2idx']),
        "trg_vocab_size": len(trg_vocab['word2idx']),
        "architecture": {
            "type": "Seq2Seq RNN-GRU with Bahdanau Attention",
            "enc_emb_dim": ENC_EMB_DIM,
            "dec_emb_dim": DEC_EMB_DIM,
            "enc_hid_dim": ENC_HID_DIM,
            "dec_hid_dim": DEC_HID_DIM,
            "bidirectional": True
        }
    }

    with open(META_PATH, 'w', encoding='utf-8') as f:
        json.dump(meta, f, indent=2, ensure_ascii=False)
    print(f"Training metadata saved to {META_PATH}")
    print(f"=== Training Completed in {duration}s ===")

if __name__ == '__main__':
    main()
