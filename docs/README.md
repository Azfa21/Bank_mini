# Dokumentasi RPL — Sistem Informasi E-Teller Bank Mini Sekolah

Folder ini memuat keluaran analisis alur sistem:

## 1. Activity Diagram (XML draw.io)
Folder: `docs/activity-diagrams/` — 28 berkas `.drawio` (XML `mxGraphModel`).
Buka dengan [app.diagrams.net](https://app.diagrams.net) atau draw.io Desktop, lalu ekspor ke PNG/PDF.

Setiap diagram memakai swimlane aktor/sistem dan mencakup alur utama serta cabang alternatif
(validasi gagal, PIN salah, saldo kurang, rollback, selisih kas, dan seterusnya).

## 2. Entity Relationship Diagram (XML draw.io)
Folder: `docs/erd/` — `erd-bank-mini.drawio` (XML `mxGraphModel`).
Memuat 10 tabel domain (users, customers, accounts, transactions, jurnal_akuntansi,
daily_reports, cash_transfers, system_configs, audit_logs, document_sequences) beserta
relasi foreign key dengan notasi crow's foot.

## 3. Skenario Use Case (teks siap Word)
Folder: `docs/usecase/`

| Berkas | Isi |
|---|---|
| `00-pendahuluan-aktor.md` | Pendahuluan, ruang lingkup, aktor, daftar use case, matriks otorisasi |
| `01-skenario-auth.md` | UC-AUTH-01 s.d. UC-AUTH-04 |
| `02-skenario-admin.md` | UC-ADM-01 s.d. UC-ADM-07 |
| `03-skenario-teller.md` | UC-TLR-01 s.d. UC-TLR-09 |
| `04-skenario-supervisor.md` | UC-SPV-01 s.d. UC-SPV-04 |
| `05-skenario-nasabah.md` | UC-NSB-01 s.d. UC-NSB-04 |
| `06-aturan-bisnis-kamus-data.md` | Aturan bisnis global, kamus data, penomoran, non-fungsional |
| `prompt-generate-word.md` | Prompt siap pakai untuk menghasilkan dokumen `.docx` via AI |

## 4. Generator
Folder: `docs/_tools/`
- `generate_diagrams.py` — menghasilkan ulang seluruh berkas `.drawio` activity diagram.
  Jalankan: `python docs/_tools/generate_diagrams.py`
- `generate_erd.py` — menghasilkan ulang berkas ERD.
  Jalankan: `python docs/_tools/generate_erd.py`

## Alur Kerja Menuju Word
1. Buka `docs/usecase/prompt-generate-word.md`.
2. Salin PROMPT INTI lalu lampirkan isi berkas 00–06.
3. Minta AI mengembalikan `.docx` (atau kode `python-docx`/Markdown siap konversi).
