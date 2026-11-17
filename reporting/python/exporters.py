"""Render only the canonical result. Exporters contain no business calculations."""

from datetime import date, datetime
from collections import Counter
from decimal import Decimal
from math import ceil
import os
from pathlib import Path
from xml.sax.saxutils import escape


def reporting_period(result):
    filters = result["meta"]["filters"]
    parts = [f"Reporting year {filters['year']}" if filters.get("year") else "Current snapshot"]
    if filters.get("quarter"):
        parts.append(f"Q{filters['quarter']}")
    if filters.get("month"):
        parts.append(f"Recorded month {filters['month']}")
    if filters.get("program_year"):
        parts.append(f"Program year {filters['program_year']}")
    return ", ".join(parts)


def metadata(result):
    meta = result["meta"]
    return [
        ("Application", "AssocMap"), ("Report", result["title"]), ("Contract", result["version"]),
        ("Reporting period", reporting_period(result)),
        ("Generated at", meta["generated_at"]), ("Timezone", meta["timezone"]),
        ("Authorized associations", "See the association scope entries below" if meta["scope"] else "No authorized associations in this scope"),
        ("Scope basis", meta["scope_basis"]), ("Currency", "PHP (Philippine peso)"),
    ] + [("Association scope " + str(index), name) for index, name in enumerate(meta["scope"], 1)] + [
        ("Filter: " + key, str(value)) for key, value in meta["filters"].items()]


def pdf(result, destination):
    import reportlab
    from reportlab.lib import colors
    from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT, TA_RIGHT
    from reportlab.lib.pagesizes import A4, landscape
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib.units import mm
    from reportlab.pdfbase import pdfmetrics
    from reportlab.pdfbase.ttfonts import TTFont
    from reportlab.pdfgen import canvas as pdf_canvas
    from reportlab.platypus import HRFlowable, Image, LongTable, Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle

    bundled = Path(reportlab.__file__).resolve().parent / "fonts"
    # Use the installed Times New Roman pair when available; keep the existing safe fallback on other hosts.
    times_dir = Path(os.environ.get("WINDIR", "C:/Windows")) / "Fonts"
    times_regular, times_bold = times_dir / "times.ttf", times_dir / "timesbd.ttf"
    use_times = times_regular.is_file() and times_bold.is_file()
    regular = os.environ.get("ASSOCMAP_REPORT_FONT_REGULAR") or str(times_regular if use_times else bundled / "Vera.ttf")
    bold = os.environ.get("ASSOCMAP_REPORT_FONT_BOLD") or str(times_bold if use_times else bundled / "VeraBd.ttf")
    for name, path in (("AssocMap", regular), ("AssocMapBold", bold)):
        font_path = Path(path)
        if not font_path.is_absolute() or not font_path.is_file():
            raise ValueError("font_unavailable")
        pdfmetrics.registerFont(TTFont(name, str(font_path)))
    pdfmetrics.registerFontFamily("AssocMap", normal="AssocMap", bold="AssocMapBold", italic="AssocMap", boldItalic="AssocMapBold")
    blue = colors.HexColor("#0B427B")
    muted = colors.HexColor("#566B7E")
    # Keep ordinary words together; long identifiers can still wrap when needed.
    style = ParagraphStyle("Cell", fontName="AssocMap", fontSize=11, leading=14, alignment=TA_LEFT,
                           textColor=colors.HexColor("#203348"), splitLongWords=True)
    numeric_style = ParagraphStyle("Number", parent=style, alignment=TA_RIGHT)
    heading = ParagraphStyle("Heading", parent=style, fontName="AssocMapBold", fontSize=12, leading=16,
                             textColor=blue, spaceBefore=12, spaceAfter=5, keepWithNext=True)
    title_style = ParagraphStyle("Title", parent=heading, fontSize=12, leading=16, spaceBefore=3, spaceAfter=7, alignment=TA_CENTER)
    caption = ParagraphStyle("Caption", parent=style, textColor=muted, fontSize=11, leading=15, spaceAfter=4, alignment=TA_JUSTIFY)
    centered = ParagraphStyle("Centered", parent=caption, alignment=TA_CENTER)
    bullet_style = ParagraphStyle("Bullet", parent=caption, leftIndent=14, firstLineIndent=-10)
    # Keep identifiers easy to scan without stretching spaces across the line.
    dash_style = ParagraphStyle("Dash", parent=caption, leftIndent=25, firstLineIndent=-10, alignment=TA_LEFT)
    long_style = ParagraphStyle("LongCell", parent=style, alignment=TA_JUSTIFY)
    table_heading = ParagraphStyle("TableHeading", parent=style, fontName="AssocMapBold", textColor=colors.white)
    numeric_heading = ParagraphStyle("NumericTableHeading", parent=table_heading, alignment=TA_RIGHT)

    def paragraph(value, selected=style):
        text = "Unobserved / not applicable" if value is None else str(value)
        glyphs = pdfmetrics.getFont(selected.fontName).face.charToGlyph
        if any(not char.isspace() and ord(char) not in glyphs for char in text):
            raise ValueError("font_missing_glyph")
        return Paragraph(escape(text).replace("\n", "<br/>"), selected)

    # Separate major sections with a subtle line in the existing palette.
    def section_title(text):
        rule = HRFlowable(width="100%", thickness=0.4, color=colors.HexColor("#DFE7EE"), spaceAfter=5)
        rule.keepWithNext = True
        return [paragraph(text.replace("current snapshot", "current records").replace("Current snapshot", "Current records"), heading), rule]

    wide = any(section["rows"] and len(section["columns"]) > 6 for section in result["sections"])
    page_size = landscape(A4) if wide else A4
    doc = SimpleDocTemplate(str(destination), pagesize=page_size, rightMargin=15 * mm, leftMargin=15 * mm,
                            topMargin=20 * mm, bottomMargin=19 * mm, title="AssocMap - " + result["title"], author="AssocMap",
                            subject=reporting_period(result))

    def table(columns, rows):
        # Align number headings with their values and shorten the busiest headings.
        labels = {"Association count": "Associations", "Municipality subtotal": "Subtotal", "Municipality/City": "Municipality / City"}
        header = [paragraph(labels.get(column["label"], column["label"]),
                            numeric_heading if column["type"] in ("integer", "decimal") or column.get("align") == "right" else table_heading)
                  for column in columns]
        body = []
        for row in rows:
            cells = []
            for column in columns:
                value = row[column["key"]]
                kind = column["type"]
                # Use the same plain wording in summary rows as in section headings.
                if value is not None and column["key"] == "section":
                    value = str(value).replace("current snapshot", "current records").replace("Current snapshot", "Current records")
                if value is not None and kind == "decimal":
                    value = format(Decimal(value), ",.2f")
                elif value is not None and kind == "integer" and column["key"] not in ("year", "program_year", "quarter", "month"):
                    value = format(value, ",")
                elif value is not None and kind == "date":
                    try:
                        value = date.fromisoformat(str(value)).strftime("%b %d, %Y")
                    except ValueError:
                        pass
                elif value is not None and column["key"] in ("status", "lifecycle"):
                    value = str(value).capitalize()
                selected = numeric_style if kind in ("integer", "decimal") or column.get("align") == "right" else long_style if value and len(str(value)) > 100 else style
                if row.get("_subtotal"):
                    selected = ParagraphStyle("SubtotalCell", parent=selected, fontName="AssocMapBold")
                cells.append(paragraph(value, selected))
            body.append(cells)
        weights = [4 if column["key"] in ("project", "training", "activity", "measure") else
                   3 if column["key"] == "association" else 2 if column["key"] in ("status", "lifecycle", "unit") else
                   2.6 if column["type"] == "text" else 1.5 if column["type"] == "date" else 1.8 for column in columns]
        rendered = LongTable([header] + body, colWidths=[doc.width * weight / sum(weights) for weight in weights],
                             repeatRows=1, splitByRow=1, splitInRow=1, hAlign="LEFT")
        rendered.setStyle(TableStyle([
            ("BACKGROUND", (0, 0), (-1, 0), blue),
            ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#F3F6F9")]),
            ("LINEBELOW", (0, 0), (-1, 0), 0.7, blue),
            ("LINEBELOW", (0, 1), (-1, -1), 0.3, colors.HexColor("#E0E7EE")),
            ("VALIGN", (0, 0), (-1, -1), "TOP"),
            ("LEFTPADDING", (0, 0), (-1, -1), 6), ("RIGHTPADDING", (0, 0), (-1, -1), 6),
            ("TOPPADDING", (0, 0), (-1, -1), 5), ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ]))
        return rendered

    try:
        generated = datetime.fromisoformat(result["meta"]["generated_at"])
        generated_text = f"{generated.strftime('%B')} {generated.day}, {generated.year}, {generated.strftime('%I:%M %p').lstrip('0')}"
        generated_text += " (Philippine Time)" if result["meta"]["timezone"] == "Asia/Manila" else " (" + result["meta"]["timezone"] + ")"
    except ValueError:
        generated_text = result["meta"]["generated_at"] + " (" + result["meta"]["timezone"] + ")"
    scope_basis = result["meta"]["scope_basis"]
    if scope_basis == "Currently assigned active, unarchived associations.":
        scope_basis = "Assigned associations that are currently active and not archived."
    # Keep the logo and name together in the center of the report header.
    logo_path = Path(__file__).resolve().parents[2] / "web" / "public" / "images" / "brand" / "assocmap-symbol-64.png"
    logo = Image(str(logo_path), width=9 * mm, height=9 * mm) if logo_path.is_file() else ""
    brand_width = pdfmetrics.stringWidth("ASSOCMAP", "AssocMap", 11) + 4 * mm
    brand = Table([[logo, paragraph("ASSOCMAP", centered)]], colWidths=[11 * mm, brand_width], hAlign="CENTER")
    brand.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "MIDDLE"), ("LEFTPADDING", (0, 0), (-1, -1), 0), ("RIGHTPADDING", (0, 0), (-1, -1), 0)]))
    header_text = [brand, paragraph(result["title"], title_style)]
    # Keep actual reporting periods visible, but omit the generic current-records label.
    if reporting_period(result) != "Current snapshot":
        header_text.append(paragraph(reporting_period(result), centered))
    header_text.append(paragraph("Generated: " + generated_text, centered))
    if result["meta"].get("prepared_by"):
        author = result["meta"]["prepared_by"]
        header_text.append(paragraph("Generated by: " + author["name"] + " — " + author["role"], centered))
    elements = header_text + [paragraph("Covers: " + scope_basis, centered)]
    # Show the covered associations near the title so readers can identify the report.
    if result["meta"]["scope"]:
        elements.append(paragraph("Associations covered: " + ", ".join(result["meta"]["scope"]), centered))
    # The line closes the report header after its coverage details.
    elements.append(HRFlowable(width="100%", thickness=0.6, color=blue, spaceBefore=4, spaceAfter=7))
    if result["empty"]:
        elements.append(paragraph("No recorded results match the selected report scope and filters.", heading))
    measures = [{"section": section["title"], "measure": item["label"],
                 "value": "Unobserved" if item["value"] is None else
                 format(Decimal(item["value"]), ",.2f") if isinstance(item["value"], str) else format(item["value"], ",")}
                for section in result["sections"] for item in section["measures"]]
    elements.extend(section_title("Summary indicators"))
    elements.append(paragraph("These figures show only what has been recorded in AssocMap. A blank means no record was entered. It does not mean zero.", caption))
    if measures:
        summary_columns = [{"key": "section", "label": "Report section", "type": "text"},
                               {"key": "measure", "label": "Indicator", "type": "text"},
                               {"key": "value", "label": "Recorded value", "type": "text", "align": "right"}]
        if len({row["section"] for row in measures}) == 1:
            elements.append(paragraph(measures[0]["section"].replace("current snapshot", "current records"), caption))
            summary_columns = summary_columns[1:]
        elements.append(table(summary_columns, measures))
    else:
        elements.append(paragraph("No summary indicators are available for this selection.", caption))

    # Combine location tables only when every original count can be reconciled.
    by_key = {section["key"]: section for section in result["sections"]}
    geo = by_key.get("association_geography")
    municipality = by_key.get("association_municipality")
    barangay = by_key.get("association_barangay")
    merge_geography = False
    if result["meta"]["filters"].get("report_type") == "associations" and geo and municipality and barangay:
        totals = {}
        for row in geo["rows"]:
            key = row["municipality_id"]
            totals[key] = (row["municipality"], row["municipality_total"])
        merge_geography = (
            Counter((row["label"], row["count"]) for row in municipality["rows"]) == Counter(totals.values())
            and Counter((row["label"], row["count"]) for row in barangay["rows"]) ==
                Counter((row["municipality"] + " / " + row["barangay"], row["count"]) for row in geo["rows"])
        )
    # Move geographic identifiers to the technical section without losing their context.
    geographic_ids = []
    for section in result["sections"]:
        if merge_geography and section["key"] in ("association_municipality", "association_barangay"):
            continue
        elements.extend(section_title("Where the associations are" if merge_geography and section["key"] == "association_geography" else section["title"]))
        if section["rows"]:
            columns = section["columns"]
            rows = section["rows"]
            if section["key"] == "association_status":
                rows = [{**row, "label": str(row["label"]).capitalize()} for row in rows]
            if section["key"] == "association_geography":
                columns = [column for column in columns if column["key"] not in ("municipality_id", "barangay_id")]
                geographic_ids.extend(
                    f"{row['municipality']} / {row['barangay']}: municipality ID {row['municipality_id']}; barangay ID {row['barangay_id']}"
                    for row in section["rows"]
                )
                if merge_geography:
                    columns = [column for column in columns if column["key"] != "municipality_total"]
                    rows = []
                    counts = Counter(row["municipality_id"] for row in section["rows"])
                    for index, row in enumerate(section["rows"]):
                        rows.append(row)
                        if counts[row["municipality_id"]] > 1 and (index + 1 == len(section["rows"]) or section["rows"][index + 1]["municipality_id"] != row["municipality_id"]):
                            rows.append({"municipality": row["municipality"], "barangay": "Municipality total", "count": row["municipality_total"], "_subtotal": True})
            elements.append(table(columns, rows))
        else:
            elements.append(paragraph("No recorded rows for this section.", caption))
        for note in section["notes"]:
            if merge_geography and section["key"] == "association_geography":
                note = "Counts include associations in the report scope, including any archived associations."
                if any(row.get("_subtotal") for row in rows):
                    note += " Municipality totals include their barangays; do not add the totals to the barangay counts."
            elements.append(paragraph("• " + note, bullet_style))
        elements.append(Spacer(1, 1 * mm))
    elements.extend(section_title("How to read this report"))
    plain_notes = {
        "Association/member counts and project statuses are current snapshots, not reconstructed historical states.":
            "These numbers show records as they are today, not as they were in the past.",
        "Program year is an administrative cohort. Reporting year/quarter selects training start dates, activity dates and monitoring reporting periods.":
            "Program year is the year group a project is filed under in the program. Reporting year and quarter decide which training dates, activity dates and monitoring periods are included.",
    }
    if result["meta"]["filters"].get("report_type") == "associations" and result["meta"]["scope_basis"] == "Currently assigned active, unarchived associations.":
        plain_notes["Archived records are retained within the current authorized association scope. Counts describe recorded data only."] = (
            "Only active assigned associations that are not archived are included. These figures show only what has been recorded in AssocMap."
        )
    elements.extend(paragraph("• " + plain_notes.get(note, note), bullet_style) for note in result["notes"])
    # Keep technical details available for support without crowding the report's opening.
    elements.extend(section_title("Technical details"))
    for label, value in metadata(result):
        if label not in ("Application", "Report", "Contract", "Authorized associations", "Reporting period", "Generated at", "Timezone", "Scope basis"):
            elements.append(paragraph("– " + label + ": " + value, dash_style))
    elements.append(paragraph("– Original generated time: " + result["meta"]["generated_at"], dash_style))
    elements.extend(paragraph("– " + value, dash_style) for value in geographic_ids)

    def footer(canvas, document):
        canvas.saveState()
        canvas.setStrokeColor(blue)
        canvas.setLineWidth(0.6)
        canvas.line(15 * mm, page_size[1] - 14 * mm, page_size[0] - 15 * mm, page_size[1] - 14 * mm)
        if document.page > 1:
            canvas.setFont("AssocMap", 11)
            canvas.setFillColor(muted)
            canvas.drawString(15 * mm, page_size[1] - 11 * mm, "AssocMap | " + result["title"])
        canvas.setStrokeColor(colors.HexColor("#DFE7EE"))
        canvas.line(15 * mm, 15 * mm, page_size[0] - 15 * mm, 15 * mm)
        canvas.setFont("AssocMap", 11)
        canvas.setFillColor(muted)
        canvas.restoreState()

    # Add total page counts only after the document has finished laying out.
    class NumberedCanvas(pdf_canvas.Canvas):
        def __init__(self, *args, **kwargs):
            super().__init__(*args, **kwargs)
            self.saved_pages = []

        def showPage(self):
            self.saved_pages.append(dict(self.__dict__))
            self._startPage()

        def save(self):
            total = len(self.saved_pages)
            for state in self.saved_pages:
                self.__dict__.update(state)
                self.setFont("AssocMap", 11)
                self.setFillColor(muted)
                self.drawRightString(page_size[0] - 15 * mm, 10 * mm, f"Page {self._pageNumber} of {total}")
                pdf_canvas.Canvas.showPage(self)
            pdf_canvas.Canvas.save(self)

    doc.build(elements, onFirstPage=footer, onLaterPages=footer, canvasmaker=NumberedCanvas)


def xlsx(result, destination):
    from openpyxl import Workbook
    from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
    from openpyxl.utils import get_column_letter
    from openpyxl.worksheet.page import PageMargins

    workbook = Workbook()
    workbook.remove(workbook.active)
    workbook.properties.creator = "AssocMap"
    workbook.properties.title = result["title"]
    workbook.properties.subject = reporting_period(result)
    blue, sage, muted = "0B427B", "63876E", "566B7E"

    def literal(cell, value):
        # Explicit string cells prevent formula injection, including leading whitespace.
        cell.value = str(value)
        cell.data_type = "s"

    def sheet(title, columns, rows, *, header_row=1, widths=None):
        ws = workbook.create_sheet(title[:31])
        ws.sheet_view.showGridLines = False
        ws.sheet_properties.tabColor = blue
        widths = widths or [38 if column["type"] == "text" else 18 for column in columns]
        for index, (column, width) in enumerate(zip(columns, widths), 1):
            ws.column_dimensions[get_column_letter(index)].width = width
            cell = ws.cell(header_row, index)
            literal(cell, column["label"])
            cell.font = Font(name="Calibri", size=10, bold=True, color="FFFFFF")
            cell.fill = PatternFill("solid", fgColor=blue)
            cell.alignment = Alignment(wrap_text=True, vertical="center")
        for row_index, row in enumerate(rows, header_row + 1):
            height = 27
            for column_index, column in enumerate(columns, 1):
                cell = ws.cell(row_index, column_index)
                value, kind = row[column["key"]], column["type"]
                if value is not None:
                    if kind == "integer":
                        cell.value = value
                        cell.number_format = "0" if column["key"] in ("year", "program_year", "quarter", "month") else "#,##0"
                    elif kind == "decimal":
                        decimal = Decimal(value)
                        # Excel numeric cells provide about 15 significant digits.
                        if len(decimal.as_tuple().digits) > 15:
                            raise ValueError("xlsx_numeric_precision_limit")
                        cell.value = decimal
                        cell.number_format = "#,##0.00"
                    elif kind == "date":
                        cell.value = date.fromisoformat(value)
                        cell.number_format = "yyyy-mm-dd"
                    else:
                        literal(cell, value)
                cell.font = Font(name="Calibri", size=10, color="203348")
                cell.alignment = Alignment(wrap_text=True, vertical="top", horizontal="right" if kind in ("integer", "decimal") else "left")
                cell.border = Border(bottom=Side(style="hair", color="E0E7EE"))
                if (row_index - header_row) % 2:
                    cell.fill = PatternFill("solid", fgColor="F1F5F9")
                if value is not None:
                    lines = sum(max(1, ceil(len(line) / max(8, widths[column_index - 1] * 1.1))) for line in str(value).split("\n"))
                    height = max(height, 15 * lines + 10)
            ws.row_dimensions[row_index].height = min(409, height)
        ws.freeze_panes = f"A{header_row + 1}"
        ws.auto_filter.ref = f"A{header_row}:{get_column_letter(len(columns))}{max(header_row, header_row + len(rows))}"
        ws.row_dimensions[header_row].height = 32
        ws.print_title_rows = f"{header_row}:{header_row}"
        ws.print_area = ws.dimensions
        ws.page_setup.orientation = "landscape" if sum(widths) > 110 else "portrait"
        ws.page_setup.paperSize = ws.PAPERSIZE_A4
        ws.page_setup.fitToWidth, ws.page_setup.fitToHeight = 1, 0
        ws.sheet_properties.pageSetUpPr.fitToPage = True
        ws.page_margins = PageMargins(left=0.3, right=0.3, top=0.65, bottom=0.55, header=0.25, footer=0.25)
        ws.oddHeader.left.text = "AssocMap - " + result["title"].replace("&", "&&")
        ws.oddHeader.right.text = reporting_period(result)
        ws.oddHeader.left.size = ws.oddHeader.right.size = 8
        ws.oddFooter.left.text = "Asia/Manila. Recorded values only"
        ws.oddFooter.right.text = "Page &P of &N"
        return ws

    summary = []
    for section in result["sections"]:
        for item in section["measures"]:
            summary.append({"section": section["title"], "measure": item["label"],
                "count": item["value"] if type(item["value"]) is int else None,
                "amount": item["value"] if isinstance(item["value"], str) else None,
                "coverage": "Unobserved" if item["value"] is None else "Recorded data only"})
    overview = sheet("Summary", [{"key": "section", "label": "Section", "type": "text"}, {"key": "measure", "label": "Measure", "type": "text"},
        {"key": "count", "label": "Count", "type": "integer"}, {"key": "amount", "label": "Recorded amount", "type": "decimal"},
        {"key": "coverage", "label": "Observation basis", "type": "text"}], summary, header_row=7, widths=[28, 62, 18, 22, 26])
    for row, text in [(1, "AssocMap - " + result["title"]), (2, reporting_period(result)),
                      (3, "Generated " + result["meta"]["generated_at"] + " (" + result["meta"]["timezone"] + ")"),
                      (4, "Scope: " + result["meta"]["scope_basis"]),
                      (6, "No recorded results match the selected scope and filters." if result["empty"] else
                       "Recorded indicators. Units follow each measure label. Blank numeric cells are unobserved / not applicable.")]:
        overview.merge_cells(start_row=row, start_column=1, end_row=row, end_column=5)
        cell = overview.cell(row, 1)
        literal(cell, text)
        cell.alignment = Alignment(wrap_text=True, vertical="center")
        cell.font = Font(name="Calibri", size=18 if row == 1 else 10, bold=row in (1, 2), color="FFFFFF" if row == 1 else muted)
        if row == 1:
            cell.fill = PatternFill("solid", fgColor=blue)
        overview.row_dimensions[row].height = 44 if row == 1 else 34 if row == 4 else 28
    overview.print_area = overview.dimensions
    quality = [{"label": "Report", "value": note} for note in result["notes"]]
    quality.append({"label": "Blank numeric/date cells", "value": "Unobserved or not applicable; a recorded numeric zero remains a numeric zero."})
    if result["empty"]:
        quality.append({"label": "Empty result", "value": "No recorded results match the selected scope and filters."})
    for index, section in enumerate(result["sections"], 1):
        sheet(f"{index:02d} {section['key'].replace('_', ' ')}", section["columns"], section["rows"])
        quality.extend({"label": section["title"], "value": note} for note in section["notes"])
        if not section["rows"]:
            quality.append({"label": section["title"], "value": "No recorded rows for this section."})
    text_columns = [{"key": "label", "label": "Item", "type": "text"}, {"key": "value", "label": "Value", "type": "text"}]
    sheet("Report Metadata", text_columns, [{"label": key, "value": value} for key, value in metadata(result)], widths=[32, 100])
    sheet("Data Quality", text_columns, quality, widths=[32, 100]).sheet_properties.tabColor = sage
    workbook.active = 0
    workbook.save(destination)
    workbook.close()
