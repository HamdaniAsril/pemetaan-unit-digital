"""Ekstrak jabatan struktural per OPD dari data SIASN menjadi Excel berhierarki."""
import collections
import sys
from pathlib import Path

import openpyxl
from openpyxl.styles import Alignment, Font, PatternFill

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "bahan" / "xxSIASN Planning Data Nov 17 2026.xlsx"
OUT = ROOT / "output" / "Struktur Jabatan per OPD.xlsx"

COL = dict(opd=4, unor=6, jabatan=7, jenis=8, jenjang=9, atasan=10, kelas=19)
LEVEL_FILL = {0: "1F4E78", 1: "BDD7EE", 2: "DDEBF7", 3: "F2F2F2"}


def eselon(jenjang):
    return jenjang.split(" - ")[0].strip()


def kategori(jenis):
    j = jenis.replace("KB-", "").strip()
    return j.title()


def load_rows():
    wb = openpyxl.load_workbook(SRC, read_only=True)
    rows = list(wb.active.iter_rows(values_only=True))[1:]
    return [r for r in rows if r[COL["jenjang"]] and r[COL["jenjang"]][0] in "IV"]


def build_tree(rows):
    """Kembalikan urutan (row, depth) per OPD, mengikuti relasi atasan."""
    by_unor = {}
    for r in rows:
        by_unor.setdefault(r[COL["unor"]], r)
    sekretariat = next(
        (r for r in rows
         if r[COL["jenis"]].strip() in ("KB-SEKRETARIAT DINAS", "KB-SEKRETARIAT BADAN",
                                        "KB-SEKRETARIAT KECAMATAN", "KB-SEKRETARIAT INSPEKTORAT")),
        None,
    )

    def parent_of(r):
        a = r[COL["atasan"]]
        if a in by_unor and by_unor[a] is not r:
            return by_unor[a]
        if a and a.startswith("SEKRETARIAT") and sekretariat is not None and sekretariat is not r:
            return sekretariat
        return None

    children = collections.defaultdict(list)
    roots = []
    for r in rows:
        p = parent_of(r)
        (children[id(p)] if p else roots).append(r)

    order = []

    def walk(r, d):
        order.append((r, d))
        # sekretariat dulu, lalu urutan asli
        kids = sorted(children[id(r)], key=lambda k: 0 if "SEKRETARI" in k[COL["jenis"]] else 1)
        for k in kids:
            walk(k, d + 1)

    for r in roots:
        walk(r, 0)
    return order


def main():
    rows = load_rows()
    by_opd = collections.OrderedDict()
    for r in rows:
        by_opd.setdefault(r[COL["opd"]], []).append(r)

    wb = openpyxl.Workbook()
    rekap = wb.active
    rekap.title = "Rekap OPD"
    rekap.append(["No", "Nama OPD", "Pimpinan", "Total Jabatan Struktural",
                  "Eselon II", "Eselon III", "Eselon IV"])

    detail = wb.create_sheet("Struktur per OPD")
    detail.append(["No", "Nama OPD", "Level", "Nama Jabatan (berjenjang)", "Nama Jabatan",
                   "Unit Organisasi", "Kategori", "Eselon", "Atasan Langsung", "Kelas Jabatan"])

    for i, (opd, rs) in enumerate(by_opd.items(), 1):
        order = build_tree(rs)
        es = collections.Counter(eselon(r[COL["jenjang"]]).split(".")[0] for r in rs)
        rekap.append([i, opd, order[0][0][COL["jabatan"]], len(rs),
                      es.get("II", 0), es.get("III", 0), es.get("IV", 0)])
        for r, d in order:
            detail.append([i, opd, d, "    " * d + r[COL["jabatan"]], r[COL["jabatan"]],
                           r[COL["unor"]], kategori(r[COL["jenis"]]), eselon(r[COL["jenjang"]]),
                           r[COL["atasan"]], r[COL["kelas"]]])
            row = detail.max_row
            if d == 0:
                for c in detail[row]:
                    c.font = Font(bold=True, color="FFFFFF")
                    c.fill = PatternFill("solid", fgColor=LEVEL_FILL[0])
            elif d == 1:
                for c in detail[row]:
                    c.font = Font(bold=True)
                    c.fill = PatternFill("solid", fgColor=LEVEL_FILL[1])

    for ws, widths in ((rekap, [5, 70, 70, 12, 10, 10, 10]),
                       (detail, [5, 45, 7, 85, 60, 55, 22, 8, 50, 8])):
        for c in ws[1]:
            c.font = Font(bold=True, color="FFFFFF")
            c.fill = PatternFill("solid", fgColor="404040")
            c.alignment = Alignment(wrap_text=True, vertical="center")
        for idx, w in enumerate(widths):
            ws.column_dimensions[openpyxl.utils.get_column_letter(idx + 1)].width = w
        ws.freeze_panes = "A2"
        ws.auto_filter.ref = ws.dimensions

    OUT.parent.mkdir(exist_ok=True)
    wb.save(OUT)
    print(f"{len(by_opd)} OPD, {len(rows)} jabatan struktural -> {OUT}", file=sys.stderr)


if __name__ == "__main__":
    main()
