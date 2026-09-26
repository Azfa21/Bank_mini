# -*- coding: utf-8 -*-
"""
Generator ERD draw.io (XML mxGraphModel) untuk
Sistem Informasi E-Teller Bank Mini Sekolah.

Menghasilkan satu file .drawio ke folder docs/erd.
Jalankan:  python docs/_tools/generate_erd.py
"""

import html
import os

OUT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "erd")
OUT_FILE = os.path.join(OUT_DIR, "erd-bank-mini.drawio")

HEADER_H = 30
ROW_H = 26
KEY_W = 46

BLUE = ("#dae8fc", "#6c8ebf")
GREEN = ("#d5e8d4", "#82b366")
ORANGE = ("#ffe6cc", "#d79b00")
YELLOW = ("#fff2cc", "#d6b656")
PURPLE = ("#e1d5e7", "#9673a6")

# (table, x, y, width, palette, [(flags, "name  TYPE"), ...])
TABLES = [
    ("users", 400, 90, 280, BLUE, [
        ("PK", "id  BIGINT"),
        ("U", "username  VARCHAR(50)"),
        ("", "password_hash  VARCHAR(255)"),
        ("", "full_name  VARCHAR(100)"),
        ("", "role  ENUM"),
        ("", "status  ENUM"),
        ("", "created_at  TIMESTAMP"),
        ("", "updated_at  TIMESTAMP"),
    ]),
    ("audit_logs", 40, 90, 280, PURPLE, [
        ("PK", "id  BIGINT"),
        ("FK", "user_id  BIGINT"),
        ("", "action  VARCHAR(50)"),
        ("", "entity_name  VARCHAR(50)"),
        ("", "entity_id  BIGINT"),
        ("", "ip_address  VARCHAR(45)"),
        ("", "metadata  JSON"),
        ("", "created_at  TIMESTAMP"),
    ]),
    ("system_configs", 780, 90, 280, PURPLE, [
        ("PK", "config_key  VARCHAR(50)"),
        ("", "config_value  VARCHAR(255)"),
        ("", "updated_at  TIMESTAMP"),
    ]),
    ("document_sequences", 1120, 90, 280, PURPLE, [
        ("PK", "id  BIGINT"),
        ("U", "key  VARCHAR(50)"),
        ("", "last_number  BIGINT"),
        ("", "created_at  TIMESTAMP"),
        ("", "updated_at  TIMESTAMP"),
    ]),
    ("customers", 40, 430, 280, GREEN, [
        ("PK", "id  BIGINT"),
        ("U", "student_number  VARCHAR(30)"),
        ("", "full_name  VARCHAR(100)"),
        ("", "class_name  VARCHAR(30)"),
        ("", "phone_number  VARCHAR(20)"),
        ("U", "qr_code_value  VARCHAR(100)"),
        ("", "pin_hash  VARCHAR(255)"),
        ("FK", "created_by  BIGINT"),
        ("", "status  ENUM"),
        ("", "failed_pin_attempts  SMALLINT"),
        ("", "locked_until  TIMESTAMP"),
        ("", "created_at  TIMESTAMP"),
    ]),
    ("accounts", 40, 880, 280, GREEN, [
        ("PK", "id  BIGINT"),
        ("U", "account_number  VARCHAR(20)"),
        ("FK,U", "customer_id  BIGINT"),
        ("", "academic_year  VARCHAR(20)"),
        ("", "opening_balance  BIGINT"),
        ("", "minimum_balance  BIGINT"),
        ("", "balance  BIGINT"),
        ("", "status  ENUM"),
        ("", "opened_at  TIMESTAMP"),
        ("", "closed_at  TIMESTAMP"),
        ("", "created_at  TIMESTAMP"),
        ("", "updated_at  TIMESTAMP"),
    ]),
    ("transactions", 420, 430, 300, ORANGE, [
        ("PK", "id  BIGINT"),
        ("FK", "account_id  BIGINT"),
        ("FK", "recipient_account_id  BIGINT"),
        ("FK", "teller_id  BIGINT"),
        ("", "slip_number  VARCHAR(30)"),
        ("", "slip_amount  BIGINT"),
        ("", "physical_cash_amount  BIGINT"),
        ("", "denomination_breakdown  JSON"),
        ("", "notes  VARCHAR(255)"),
        ("FK", "reversal_of_transaction_id  BIGINT"),
        ("", "reversed_at  TIMESTAMP"),
        ("FK", "reversed_by  BIGINT"),
        ("", "reversal_reason  VARCHAR(255)"),
        ("", "type  VARCHAR(20)"),
        ("", "amount  BIGINT"),
        ("", "admin_fee  BIGINT"),
        ("", "balance_before  BIGINT"),
        ("", "balance_after  BIGINT"),
        ("", "status  ENUM"),
        ("", "transaction_at  TIMESTAMP"),
        ("U", "receipt_number  VARCHAR(30)"),
        ("", "created_at  TIMESTAMP"),
    ]),
    ("jurnal_akuntansi", 820, 430, 280, ORANGE, [
        ("PK", "id  BIGINT"),
        ("FK", "transaction_id  BIGINT"),
        ("FK", "cash_transfer_id  BIGINT"),
        ("", "journal_number  VARCHAR(30)"),
        ("", "account_code  VARCHAR(10)"),
        ("", "entry_type  ENUM"),
        ("", "amount  BIGINT"),
        ("", "debit_amount  BIGINT"),
        ("", "credit_amount  BIGINT"),
        ("", "description  VARCHAR(255)"),
        ("", "status  ENUM"),
        ("", "journal_at  TIMESTAMP"),
        ("", "created_at  TIMESTAMP"),
    ]),
    ("daily_reports", 420, 1180, 300, YELLOW, [
        ("PK", "id  BIGINT"),
        ("FK", "teller_id  BIGINT"),
        ("U", "report_number  VARCHAR(30)"),
        ("", "report_date  DATE"),
        ("", "opening_balance  BIGINT"),
        ("", "total_deposit  BIGINT"),
        ("", "total_withdrawal  BIGINT"),
        ("", "total_admin_fee  BIGINT"),
        ("", "closing_balance_system  BIGINT"),
        ("", "closing_balance_physical  BIGINT"),
        ("", "closing_breakdown  JSON"),
        ("", "cash_difference  BIGINT"),
        ("", "status  ENUM"),
        ("FK", "approved_by  BIGINT"),
        ("", "approved_at  TIMESTAMP"),
        ("", "submitted_at  TIMESTAMP"),
        ("", "approval_notes  TEXT"),
        ("", "created_at  TIMESTAMP"),
    ]),
    ("cash_transfers", 820, 900, 280, YELLOW, [
        ("PK", "id  BIGINT"),
        ("FK,U", "report_id  BIGINT"),
        ("FK", "supervisor_id  BIGINT"),
        ("", "amount  BIGINT"),
        ("", "source_location  VARCHAR(50)"),
        ("", "destination_location  VARCHAR(50)"),
        ("", "status  ENUM"),
        ("", "transferred_at  TIMESTAMP"),
        ("", "notes  VARCHAR(255)"),
        ("", "created_at  TIMESTAMP"),
    ]),
]

# (source, target, label, cardinality) ; cardinality: "1N" atau "11"
RELATIONS = [
    ("users", "customers", "created_by", "1N"),
    ("users", "transactions", "teller_id", "1N"),
    ("users", "transactions", "reversed_by", "1N"),
    ("users", "daily_reports", "teller_id", "1N"),
    ("users", "daily_reports", "approved_by", "1N"),
    ("users", "cash_transfers", "supervisor_id", "1N"),
    ("users", "audit_logs", "user_id", "1N"),
    ("customers", "accounts", "customer_id", "11"),
    ("accounts", "transactions", "account_id", "1N"),
    ("accounts", "transactions", "recipient_account_id", "1N"),
    ("transactions", "transactions", "reversal_of_transaction_id", "1N"),
    ("transactions", "jurnal_akuntansi", "transaction_id", "1N"),
    ("cash_transfers", "jurnal_akuntansi", "cash_transfer_id", "1N"),
    ("daily_reports", "cash_transfers", "report_id", "11"),
]


def esc(value):
    return html.escape(value, quote=True)


def table_height(columns):
    return HEADER_H + len(columns) * ROW_H


def table_xml(name, x, y, width, palette, columns):
    fill, stroke = palette
    height = table_height(columns)
    tid = "tbl_" + name
    out = []
    out.append(
        '        <mxCell id="%s" value="%s" style="shape=table;startSize=%d;container=1;'
        'collapsible=0;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;'
        'align=center;resizeLast=1;fillColor=%s;strokeColor=%s;fontSize=12;" '
        'vertex="1" parent="1">' % (tid, esc(name), HEADER_H, fill, stroke)
    )
    out.append(
        '          <mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/>'
        % (x, y, width, height)
    )
    out.append("        </mxCell>")

    right_w = width - KEY_W
    for i, (flags, label) in enumerate(columns, start=1):
        rid = "%s_r%d" % (tid, i)
        cy = HEADER_H + (i - 1) * ROW_H
        key_fill = "none"
        if "PK" in flags:
            key_fill = "#f8cecc"
        elif "FK" in flags:
            key_fill = "#dae8fc"
        out.append(
            '        <mxCell id="%s" value="" style="shape=tableRow;horizontal=0;startSize=0;'
            'swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;'
            'points=[[0,0.5,0],[1,0.5,0]];portConstraint=eastwest;top=0;left=0;right=0;'
            'bottom=0;" vertex="1" parent="%s">' % (rid, tid)
        )
        out.append(
            '          <mxGeometry y="%d" width="%d" height="%d" as="geometry"/>'
            % (cy, width, ROW_H)
        )
        out.append("        </mxCell>")
        out.append(
            '        <mxCell id="%sc1" value="%s" style="shape=partialRectangle;overflow=hidden;'
            'connectable=0;fillColor=%s;top=0;left=0;bottom=0;right=0;fontStyle=1;'
            'align=center;fontSize=9;" vertex="1" parent="%s">'
            % (rid, esc(flags), key_fill, rid)
        )
        out.append(
            '          <mxGeometry width="%d" height="%d" as="geometry"/>' % (KEY_W, ROW_H)
        )
        out.append("        </mxCell>")
        out.append(
            '        <mxCell id="%sc2" value="%s" style="shape=partialRectangle;overflow=hidden;'
            'connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;'
            'spacingLeft=8;fontSize=11;" vertex="1" parent="%s">' % (rid, esc(label), rid)
        )
        out.append(
            '          <mxGeometry x="%d" width="%d" height="%d" as="geometry"/>'
            % (KEY_W, right_w, ROW_H)
        )
        out.append("        </mxCell>")
    return "\n".join(out)


def relation_xml(idx, source, target, label, card):
    start_arrow = "ERone"
    end_arrow = "ERmany" if card == "1N" else "ERone"
    style = (
        "edgeStyle=entityRelationEdgeStyle;fontSize=11;html=1;endArrow=%s;"
        "startArrow=%s;rounded=0;strokeColor=#333333;startFill=0;endFill=0;"
        % (end_arrow, start_arrow)
    )
    if source == target:
        style = (
            "edgeStyle=entityRelationEdgeStyle;fontSize=11;html=1;endArrow=ERmany;"
            "startArrow=ERone;rounded=0;strokeColor=#333333;startFill=0;endFill=0;"
            "exitX=0.5;exitY=1;entryX=0.5;entryY=0.5;"
        )
    return (
        '        <mxCell id="rel_%d" value="%s" style="%s" edge="1" parent="1" '
        'source="tbl_%s" target="tbl_%s">\n'
        '          <mxGeometry relative="1" as="geometry"/>\n'
        "        </mxCell>" % (idx, esc(label), style, source, target)
    )


def build():
    body = []
    body.append(
        '        <mxCell id="title" value="ERD - Sistem Informasi E-Teller Bank Mini Sekolah" '
        'style="text;html=1;fontSize=18;fontStyle=1;align=left;verticalAlign=middle;" '
        'vertex="1" parent="1">'
    )
    body.append('          <mxGeometry x="40" y="20" width="700" height="40" as="geometry"/>')
    body.append("        </mxCell>")

    legend = (
        "Legenda:  PK = Primary Key   |   FK = Foreign Key   |   U = Unique   |   "
        "Garis crow's foot: satu (1) ke banyak (N) / satu ke satu (1:1)"
    )
    body.append(
        '        <mxCell id="legend" value="%s" style="text;html=1;fontSize=11;align=left;'
        'verticalAlign=middle;fontColor=#555555;" vertex="1" parent="1">' % esc(legend)
    )
    body.append('          <mxGeometry x="40" y="58" width="1200" height="24" as="geometry"/>')
    body.append("        </mxCell>")

    for name, x, y, width, palette, columns in TABLES:
        body.append(table_xml(name, x, y, width, palette, columns))

    for idx, (source, target, label, card) in enumerate(RELATIONS, start=1):
        body.append(relation_xml(idx, source, target, label, card))

    content = "\n".join(body)
    return (
        '<mxfile host="app.diagrams.net" agent="opencode" version="24.7.17" type="device">\n'
        '  <diagram id="erd-bank-mini" name="ERD - Bank Mini">\n'
        '    <mxGraphModel dx="1400" dy="1000" grid="1" gridSize="10" guides="1" '
        'tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" '
        'pageWidth="1600" pageHeight="1800" math="0" shadow="0">\n'
        "      <root>\n"
        '        <mxCell id="0"/>\n'
        '        <mxCell id="1" parent="0"/>\n'
        + content
        + "\n      </root>\n    </mxGraphModel>\n  </diagram>\n</mxfile>\n"
    )


def main():
    os.makedirs(OUT_DIR, exist_ok=True)
    with open(OUT_FILE, "w", encoding="utf-8") as handle:
        handle.write(build())
    print("Ditulis:", OUT_FILE)


if __name__ == "__main__":
    main()
