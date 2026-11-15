class OfficialMember {
  const OfficialMember(this.name);
  final String name;

  factory OfficialMember.fromJson(Map<String, dynamic> json) =>
      OfficialMember(_text(json['name']));
}

class AssociationTraining {
  const AssociationTraining({
    required this.id,
    required this.title,
    required this.status,
    required this.programYear,
    required this.isArchived,
    this.startDate,
    this.endDate,
    this.venue,
    this.conductedBy,
    this.relatedProject,
    this.description,
  });
  final String id, title, status;
  final int programYear;
  final bool isArchived;
  final DateTime? startDate, endDate;
  final String? venue, conductedBy, relatedProject, description;

  factory AssociationTraining.fromJson(Map<String, dynamic> json) {
    final status = _text(json['status']);
    if (!{'scheduled', 'completed', 'cancelled'}.contains(status) ||
        json['is_archived'] is! bool) {
      throw const FormatException('Invalid training status.');
    }
    return AssociationTraining(
      id: _text(json['id']),
      title: _text(json['title']),
      status: status,
      programYear: _count(json['program_year'], minimum: 1),
      isArchived: json['is_archived'] as bool,
      startDate: _date(json['start_date']),
      endDate: _date(json['end_date']),
      venue: _optional(json['venue']),
      conductedBy: _optional(json['conducted_by']),
      relatedProject: _optional(json['related_project']),
      description: _optional(json['description']),
    );
  }
}

class AssociationRecordsPage<T> {
  const AssociationRecordsPage({
    required this.associationId,
    required this.records,
    required this.page,
    required this.lastPage,
    required this.total,
  });
  final String associationId;
  final List<T> records;
  final int page, lastPage, total;

  factory AssociationRecordsPage.fromJson(
    Map<String, dynamic> json,
    String key,
    T Function(Map<String, dynamic>) parse,
  ) {
    final rows = json[key];
    final page = _count(json['page'], minimum: 1);
    final lastPage = _count(json['last_page'], minimum: 1);
    final total = _count(json['total']);
    if (rows is! List ||
        rows.length > 25 ||
        rows.length > total ||
        page > lastPage) {
      throw const FormatException('Invalid association records page.');
    }
    return AssociationRecordsPage(
      associationId: _text(json['association_id']),
      records: List.unmodifiable(
        rows.map((row) {
          if (row is! Map<String, dynamic>) {
            throw const FormatException('Invalid record.');
          }
          return parse(row);
        }),
      ),
      page: page,
      lastPage: lastPage,
      total: total,
    );
  }
}

String _text(dynamic value) {
  if (value is! String || value.trim().isEmpty) {
    throw const FormatException('Invalid text.');
  }
  return value.trim();
}

String? _optional(dynamic value) => value == null
    ? null
    : (value is String && value.trim().isEmpty ? null : _text(value));
int _count(dynamic value, {int minimum = 0}) {
  if (value is! int || value < minimum) {
    throw const FormatException('Invalid count.');
  }
  return value;
}

DateTime? _date(dynamic value) {
  if (value == null) return null;
  final text = _text(value);
  final date = DateTime.tryParse(text);
  if (!RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(text) ||
      date == null ||
      date.toIso8601String().substring(0, 10) != text) {
    throw const FormatException('Invalid training date.');
  }
  return date;
}
