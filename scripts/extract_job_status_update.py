"""Convert the Job Status Update workbook to a reviewable JSON import file.

Only dates typed as day-first text are imported. Excel date cells are kept in
the job notes for verification because this workbook has mixed date formats.
"""

import argparse
import datetime as dt
import json
import re
from pathlib import Path

from openpyxl import load_workbook


def source_text(value):
    if value is None:
        return ""
    if isinstance(value, (dt.datetime, dt.date)):
        return value.strftime("%Y-%m-%d")
    return str(value).strip()


def safe_date(value, notes, label):
    if value is None or str(value).strip() == "":
        return None
    if isinstance(value, (dt.datetime, dt.date)):
        notes.append(f"{label} (Excel date; verify): {source_text(value)}")
        return None
    raw = source_text(value).strip(" `")
    match = re.fullmatch(r"(\d{1,2})[-/](\d{1,2})[-/](\d{4})", raw)
    if match:
        try:
            return dt.date(int(match[3]), int(match[2]), int(match[1])).isoformat()
        except ValueError:
            pass
    notes.append(f"{label} (unparsed; verify): {raw}")
    return None


def normalize_number(value):
    match = re.fullmatch(r"AMS-+(\d{4})", source_text(value), re.IGNORECASE)
    if not match:
        raise ValueError(f"Unexpected job number: {value!r}")
    return f"AMS-{match[1]}"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("workbook", type=Path)
    parser.add_argument("output", type=Path)
    args = parser.parse_args()

    sheet = load_workbook(args.workbook, read_only=True, data_only=True).active
    headers = [source_text(cell.value).lower() for cell in next(sheet.rows)]
    expected = [
        "job no", "client", "names", "job received on",
        "printer deadline by client", "job forwarded to production",
        "job completed on", "days passed", "comments",
    ]
    if headers[:9] != expected:
        raise ValueError(f"Unexpected spreadsheet columns: {headers[:9]!r}")

    jobs = []
    seen = set()
    for row_number, cells in enumerate(sheet.iter_rows(min_row=2, values_only=True), start=2):
        values = list(cells[:9])
        if not any(value is not None and str(value).strip() for value in values):
            continue
        number = normalize_number(values[0])
        if number in seen:
            raise ValueError(f"Duplicate {number} on row {row_number}")
        seen.add(number)
        title = source_text(values[2])
        if not title:
            raise ValueError(f"Missing name on row {row_number}")

        notes = [f"Imported from Job Status Update.xlsx, row {row_number}."]
        if source_text(values[0]) != number:
            notes.append(f"Original job number in spreadsheet: {source_text(values[0])}")
        received = safe_date(values[3], notes, "Job received on")
        due = safe_date(values[4], notes, "Printer deadline by client")
        forwarded = source_text(values[5])
        completed = safe_date(values[6], notes, "Job completed on")
        if completed:
            notes.append(f"Job completed on: {completed}")
        if forwarded:
            notes.append(f"Original production progress: {forwarded}")
        if values[7] is not None:
            notes.append(f"Original days passed: {source_text(values[7])}")
        comments = source_text(values[8])
        if comments:
            notes.append(f"Original comments: {comments}")

        is_complete = forwarded.lower() == "complete" or comments.lower() == "complete"
        stage = "close" if is_complete else None
        status = "delivered" if is_complete else "designing"
        if not is_complete and "lamination" in comments.lower():
            stage, status = "lamination", "lamination"
        elif not is_complete and "printing" in comments.lower():
            stage, status = "printing", "printing"
        elif not is_complete and "dummy" in comments.lower():
            stage, status = "dummy", "mockup"

        jobs.append({
            "job_number": number,
            "client_name": source_text(values[1]) or None,
            "title": title,
            "received_date": received,
            "due_date": due,
            "completed_date": completed,
            "status": status,
            "production_stage": stage,
            "details": "\n".join(notes),
        })

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(jobs, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Prepared {len(jobs)} jobs: {min(seen)} to {max(seen)}")
    print(f"Dates needing verification: {sum('verify' in job['details'] for job in jobs)}")


if __name__ == "__main__":
    main()
