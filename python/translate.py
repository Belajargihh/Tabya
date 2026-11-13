"""
Tabya NMT Inference / Translator
Translates English text into Yenbuba (Biak-Beteu) using PyTorch Seq2Seq Model
"""

import os
import sys
import json
import re
import torch
from model import Encoder, Decoder, Seq2Seq

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

WEIGHTS_PATH = os.path.join(BASE_DIR, 'model_weights.pth')
VOCAB_PATH = os.path.join(BASE_DIR, 'vocab.json')

def clean_text(text):
    text = text.lower().strip()
    text = re.sub(r'[^\w\s]', '', text)
    return text

_LOADED_MODEL = None
_LOADED_VOCABS = None

def get_model_and_vocabs():
    global _LOADED_MODEL, _LOADED_VOCABS
    if _LOADED_MODEL is not None and _LOADED_VOCABS is not None:
        return _LOADED_MODEL, _LOADED_VOCABS

    if not os.path.exists(WEIGHTS_PATH) or not os.path.exists(VOCAB_PATH):
        return None, None

    with open(VOCAB_PATH, 'r', encoding='utf-8') as f:
        vocabs = json.load(f)

    src_vocab = vocabs['src_vocab']
    trg_vocab = vocabs['trg_vocab']

    INPUT_DIM = len(src_vocab['word2idx'])
    OUTPUT_DIM = len(trg_vocab['word2idx'])
    ENC_EMB_DIM = 64
    DEC_EMB_DIM = 64
    ENC_HID_DIM = 128
    DEC_HID_DIM = 128
    DEVICE = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

    enc = Encoder(INPUT_DIM, ENC_EMB_DIM, ENC_HID_DIM, DEC_HID_DIM)
    dec = Decoder(OUTPUT_DIM, DEC_EMB_DIM, ENC_HID_DIM, DEC_HID_DIM)
    model = Seq2Seq(enc, dec, DEVICE).to(DEVICE)
    model.load_state_dict(torch.load(WEIGHTS_PATH, map_location=DEVICE))
    model.eval()

    _LOADED_MODEL = model
    _LOADED_VOCABS = (src_vocab, trg_vocab, DEVICE)
    return _LOADED_MODEL, _LOADED_VOCABS

def translate(text):
    if not text:
        return {"success": False, "error": "Empty input text"}

    model, vocabs_data = get_model_and_vocabs()
    if model is None:
        return {"success": False, "error": "Model or vocab file not found. Please train model first."}

    src_vocab, trg_vocab, device = vocabs_data

    cleaned = clean_text(text)
    tokens = cleaned.split()
    if not tokens:
        return {"success": False, "error": "No valid tokens found"}

    w2i = src_vocab['word2idx']
    unk_idx = w2i['<unk>']
    src_indices = [w2i.get(t, unk_idx) for t in tokens]
    oov_count = sum(1 for idx in src_indices if idx == unk_idx)

    src_tensor = torch.tensor([src_indices], dtype=torch.long, device=device)
    translated_tokens, attentions = model.translate_sentence(src_tensor, src_vocab, trg_vocab)

    translated_text = " ".join(translated_tokens)
    confidence = max(0.40, min(0.99, 1.0 - (oov_count * 0.35)))

    return {
        "success": True,
        "source_text": text,
        "cleaned_tokens": tokens,
        "translated_text": translated_text,
        "translated_tokens": translated_tokens,
        "method": "NMT (Seq2Seq GRU + Attention)",
        "confidence": round(confidence * 100, 2),
        "oov_tokens": [tokens[i] for i, idx in enumerate(src_indices) if idx == unk_idx]
    }

if __name__ == '__main__':
    if len(sys.argv) > 1:
        input_text = " ".join(sys.argv[1:])
    else:
        input_text = "where is the jetty"

    result = translate(input_text)
    print(json.dumps(result, ensure_ascii=False, indent=2))
