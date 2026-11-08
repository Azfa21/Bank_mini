import 'dart:typed_data';

import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import '../core/utils/formatters.dart';
import '../models/statement.dart';
import '../models/transaction.dart';

class StatementPdfService {
  const StatementPdfService();

  Future<Uint8List> build(StatementData data) async {
    final doc = pw.Document();
    final dateFormat = DateFormat('dd MMM yyyy', 'id_ID');

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(28),
        header: (context) => _header(data),
        footer: (context) => _footer(context, data),
        build: (context) => [
          pw.SizedBox(height: 12),
          _accountBlock(data),
          pw.SizedBox(height: 14),
          _summaryBlock(data),
          pw.SizedBox(height: 16),
          _transactionTable(data, dateFormat),
        ],
      ),
    );

    return doc.save();
  }

  pw.Widget _header(StatementData data) {
    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Row(
          mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
          children: [
            pw.Text(
              'e-STATEMENT',
              style: pw.TextStyle(
                fontSize: 18,
                fontWeight: pw.FontWeight.bold,
                color: PdfColor.fromHex('#743722'),
              ),
            ),
            pw.Text(
              'Kode Verifikasi: ${data.verificationCode}',
              style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey700),
            ),
          ],
        ),
        pw.SizedBox(height: 4),
        pw.Text(
          'Rekening Koran Nasabah (e-Statement)',
          style: const pw.TextStyle(fontSize: 11, color: PdfColors.grey700),
        ),
        pw.Divider(color: PdfColor.fromHex('#743722')),
      ],
    );
  }

  pw.Widget _accountBlock(StatementData data) {
    pw.Widget row(String label, String value) => pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 2),
      child: pw.Row(
        children: [
          pw.SizedBox(
            width: 130,
            child: pw.Text(
              label,
              style: const pw.TextStyle(fontSize: 10, color: PdfColors.grey700),
            ),
          ),
          pw.Text(
            value,
            style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold),
          ),
        ],
      ),
    );

    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        row('Nama Nasabah', data.fullName),
        row('NIS', data.studentNumber),
        if (data.className != null) row('Kelas', data.className!),
        row('Nomor Rekening', data.account.accountNumber),
        row('Periode', '${data.dateFrom} s/d ${data.dateTo}'),
        row('Saldo Akhir', Formatters.rupiah(data.account.balance)),
      ],
    );
  }

  pw.Widget _summaryBlock(StatementData data) {
    pw.Widget item(String label, int value) => pw.Expanded(
      child: pw.Container(
        padding: const pw.EdgeInsets.all(8),
        margin: const pw.EdgeInsets.symmetric(horizontal: 2),
        decoration: pw.BoxDecoration(
          color: PdfColor.fromHex('#F5F5F4'),
          borderRadius: pw.BorderRadius.circular(6),
        ),
        child: pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            pw.Text(
              label,
              style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey700),
            ),
            pw.SizedBox(height: 3),
            pw.Text(
              Formatters.rupiah(value),
              style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold),
            ),
          ],
        ),
      ),
    );

    return pw.Row(
      children: [
        item('Setoran', data.summary.totalDeposit),
        item('Penarikan', data.summary.totalWithdrawal),
        item('Transfer Masuk', data.summary.totalTransferIn),
        item('Transfer Keluar', data.summary.totalTransferOut),
      ],
    );
  }

  pw.Widget _transactionTable(StatementData data, DateFormat dateFormat) {
    final headers = ['Tanggal', 'Jenis', 'Nominal', 'Saldo'];

    final rows = data.transactions.map((transaction) {
      return [
        transaction.transactionAt == null
            ? '-'
            : dateFormat.format(transaction.transactionAt!.toLocal()),
        _typeLabel(transaction),
        '${transaction.isIncoming ? '+' : '-'}${Formatters.rupiah(transaction.amount)}',
        Formatters.rupiah(transaction.balanceAfter),
      ];
    }).toList();

    return pw.TableHelper.fromTextArray(
      headers: headers,
      data: rows,
      border: pw.TableBorder.all(color: PdfColors.grey300, width: 0.5),
      headerStyle: pw.TextStyle(
        fontSize: 9,
        fontWeight: pw.FontWeight.bold,
        color: PdfColors.white,
      ),
      headerDecoration: pw.BoxDecoration(color: PdfColor.fromHex('#743722')),
      cellStyle: const pw.TextStyle(fontSize: 8.5),
      cellAlignments: {
        0: pw.Alignment.centerLeft,
        1: pw.Alignment.centerLeft,
        2: pw.Alignment.centerRight,
        3: pw.Alignment.centerRight,
      },
      cellPadding: const pw.EdgeInsets.symmetric(horizontal: 5, vertical: 4),
    );
  }

  pw.Widget _footer(pw.Context context, StatementData data) {
    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Divider(color: PdfColors.grey400),
        pw.Text(
          'Dokumen ini dicetak otomatis oleh aplikasi Bank Mini Nasabah. '
          'Kode verifikasi: ${data.verificationCode}.',
          style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey700),
        ),
        pw.SizedBox(height: 3),
        pw.Text(
          'Halaman ${context.pageNumber} dari ${context.pagesCount}',
          style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey700),
        ),
        pw.SizedBox(height: 3),
        pw.Text(
          '© 2026 Bank Mini Skansaba. Seluruh hak cipta dilindungi.',
          style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey700),
        ),
      ],
    );
  }

  String _typeLabel(Transaction transaction) {
    if (transaction.type == 'transfer' && transaction.counterpart != null) {
      final target =
          transaction.counterpart!.name ??
          transaction.counterpart!.accountNumber;
      return 'Transfer ${transaction.isIncoming ? 'dari' : 'ke'} $target';
    }

    return transaction.typeLabel;
  }
}
