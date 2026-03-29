#!/usr/bin/env python3
import json
import re
import sys
from pathlib import Path

from pypdf import PdfReader


PAGE_HEADER_RE = re.compile(r"^Questions and Answers PDF \d+/\d+$")
PAGE_MARKER_RE = re.compile(r"^--\s*\d+\s+of\s+\d+\s*--$")
OPTION_RE = re.compile(r"^([A-H])[\.\)]\s*(.+)$")
QUESTION_RE = re.compile(r"^Question:\s*(\d+)\s*$", re.IGNORECASE)
ANSWER_RE = re.compile(r"^Answer:\s*(.*)$", re.IGNORECASE)


def normalize_line(line: str) -> str:
    return re.sub(r"\s+", " ", line).strip()


def clean_lines(text: str):
    cleaned = []
    for raw in text.splitlines():
        line = normalize_line(raw)
        if not line:
            continue
        if PAGE_HEADER_RE.match(line):
            continue
        if PAGE_MARKER_RE.match(line):
            continue
        cleaned.append(line)
    return cleaned


def parse_answer_letters(answer_text: str):
    # Supports: "B", "CD", "A, D", "A and D"
    letters = re.findall(r"[A-H]", answer_text.upper())
    out = []
    for ch in letters:
        if ch not in out:
            out.append(ch)
    return out


def build_question(question_no: int, block_lines):
    option_starts = []
    for idx, ln in enumerate(block_lines):
        if OPTION_RE.match(ln):
            option_starts.append(idx)

    answer_idx = None
    answer_text = ""
    for idx, ln in enumerate(block_lines):
        m = ANSWER_RE.match(ln)
        if m:
            answer_idx = idx
            answer_text = m.group(1).strip()
            break

    if not option_starts or answer_idx is None:
        return None

    first_opt_idx = option_starts[0]
    stem_lines = []
    for ln in block_lines[:first_opt_idx]:
        if ln.upper() in {"HOTSPOT", "DRAG DROP"}:
            continue
        stem_lines.append(ln)

    prompt = " ".join(stem_lines).strip()
    if not prompt:
        return None

    options_map = {}
    for i, start in enumerate(option_starts):
        end = option_starts[i + 1] if i + 1 < len(option_starts) else answer_idx
        first = block_lines[start]
        m = OPTION_RE.match(first)
        if not m:
            continue
        key = m.group(1)
        text_parts = [m.group(2).strip()]
        for ln in block_lines[start + 1 : end]:
            if ANSWER_RE.match(ln):
                break
            if ln.lower().startswith("explanation:") or ln.lower().startswith("reference:"):
                break
            text_parts.append(ln.strip())
        options_map[key] = " ".join(text_parts).strip()

    if len(options_map) < 2:
        return None

    correct_letters = parse_answer_letters(answer_text)
    if not correct_letters:
        return None
    if any(letter not in options_map for letter in correct_letters):
        return None

    letters_in_order = sorted(options_map.keys())
    options = [options_map[letter] for letter in letters_in_order]
    correct_indexes = [letters_in_order.index(letter) for letter in correct_letters]

    return {
        "source_question_no": question_no,
        "prompt": prompt,
        "type": "multi_choice" if len(correct_indexes) > 1 else "choice",
        "options": options,
        "correct_indexes": correct_indexes,
    }


def parse_questions(lines):
    questions = []
    current_no = None
    current_block = []

    def flush():
        nonlocal current_no, current_block
        if current_no is None:
            return
        q = build_question(current_no, current_block)
        if q:
            questions.append(q)
        current_no = None
        current_block = []

    for line in lines:
        qm = QUESTION_RE.match(line)
        if qm:
            flush()
            current_no = int(qm.group(1))
            continue
        if current_no is not None:
            current_block.append(line)
    flush()
    return questions


def main():
    if len(sys.argv) != 3:
        print("Usage: import_sc200_pdf.py <pdf_path> <output_json_path>")
        sys.exit(1)

    pdf_path = Path(sys.argv[1])
    out_path = Path(sys.argv[2])
    reader = PdfReader(str(pdf_path))

    all_lines = []
    for page in reader.pages:
        text = page.extract_text() or ""
        all_lines.extend(clean_lines(text))

    questions = parse_questions(all_lines)
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(questions, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"Parsed {len(questions)} choice/multi_choice questions")
    print(f"Output: {out_path}")


if __name__ == "__main__":
    main()

