"""
Tabya NMT Evaluation Tool - BLEU Score Calculator
Computes BLEU-1, BLEU-2, BLEU-3, BLEU-4 and Cumulative BLEU
Using NLTK SmoothingFunction for Academic Evaluation (Bab 4)
"""

import os
import sys
import json
import re
import nltk
from nltk.translate.bleu_score import sentence_bleu, SmoothingFunction

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from translate import translate, clean_text

DATASET_PATH = os.path.join(BASE_DIR, 'dataset.json')

def calculate_bleu(candidate_tokens, reference_tokens_list):
    chencherry = SmoothingFunction()
    # Weights for BLEU-1 to 4
    w1 = (1.0, 0, 0, 0)
    w2 = (0.5, 0.5, 0, 0)
    w3 = (0.333, 0.333, 0.333, 0)
    w4 = (0.25, 0.25, 0.25, 0.25)

    b1 = sentence_bleu(reference_tokens_list, candidate_tokens, weights=w1, smoothing_function=chencherry.method1)
    b2 = sentence_bleu(reference_tokens_list, candidate_tokens, weights=w2, smoothing_function=chencherry.method1)
    b3 = sentence_bleu(reference_tokens_list, candidate_tokens, weights=w3, smoothing_function=chencherry.method1)
    b4 = sentence_bleu(reference_tokens_list, candidate_tokens, weights=w4, smoothing_function=chencherry.method1)

    return {
        "bleu_1": round(b1 * 100, 2),
        "bleu_2": round(b2 * 100, 2),
        "bleu_3": round(b3 * 100, 2),
        "bleu_4": round(b4 * 100, 2),
        "cumulative_bleu": round(b4 * 100, 2)
    }

def run_evaluation(sample_size=15):
    if not os.path.exists(DATASET_PATH):
        return {"error": "Dataset not found"}

    with open(DATASET_PATH, 'r', encoding='utf-8') as f:
        data = json.load(f)

    # Pick representative samples across categories (greetings, numbers, sentences, words)
    categories = ['greeting', 'number', 'sentence', 'word', 'phrase']
    selected = []

    for cat in categories:
        cat_items = [d for d in data if d.get('category') == cat]
        selected.extend(cat_items[:4])

    if len(selected) > sample_size:
        selected = selected[:sample_size]

    results = []
    total_b1, total_b2, total_b3, total_b4 = 0, 0, 0, 0

    for idx, item in enumerate(selected, 1):
        src = item['english_text']
        ref = item['yenbuba_text']
        cat = item.get('category', 'general')

        trans = translate(src)
        cand_text = trans.get('translated_text', '')

        cand_tokens = cand_text.split()
        ref_tokens = ref.lower().strip().split()

        scores = calculate_bleu(cand_tokens, [ref_tokens])

        total_b1 += scores['bleu_1']
        total_b2 += scores['bleu_2']
        total_b3 += scores['bleu_3']
        total_b4 += scores['bleu_4']

        results.append({
            "no": idx,
            "category": cat,
            "source": src,
            "reference": ref,
            "candidate": cand_text,
            "bleu_1": scores['bleu_1'],
            "bleu_2": scores['bleu_2'],
            "bleu_3": scores['bleu_3'],
            "bleu_4": scores['bleu_4'],
            "match": cand_text.lower() == ref.lower()
        })

    n = max(1, len(results))
    avg_scores = {
        "avg_bleu_1": round(total_b1 / n, 2),
        "avg_bleu_2": round(total_b2 / n, 2),
        "avg_bleu_3": round(total_b3 / n, 2),
        "avg_bleu_4": round(total_b4 / n, 2),
        "sample_count": n
    }

    output_res = {
        "summary": avg_scores,
        "details": results
    }

    results_file = os.path.join(BASE_DIR, 'bleu_results.json')
    with open(results_file, 'w', encoding='utf-8') as f:
        json.dump(output_res, f, ensure_ascii=False, indent=2)

    return output_res

if __name__ == '__main__':
    eval_res = run_evaluation(15)
    print(json.dumps(eval_res, ensure_ascii=False, indent=2))
