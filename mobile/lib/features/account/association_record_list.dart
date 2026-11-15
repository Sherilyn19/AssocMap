import 'package:flutter/material.dart';
import '../../core/widgets/assocmap_widgets.dart';
import '../../data/models/association_records.dart';
import '../../data/models/auth_session.dart';
import '../../data/repositories/association_records_repository.dart';

class AssociationRecordList extends StatefulWidget {
  const AssociationRecordList({
    super.key,
    required this.repository,
    required this.session,
    required this.onSessionExpired,
    this.onRefresh,
    this.trainings = false,
  });
  final AssociationRecordsRepository repository;
  final AuthSession session;
  final VoidCallback onSessionExpired;
  final Future<void> Function()? onRefresh;
  final bool trainings;
  @override
  State<AssociationRecordList> createState() => _AssociationRecordListState();
}

class _AssociationRecordListState extends State<AssociationRecordList> {
  final List<OfficialMember> _members = [];
  final List<AssociationTraining> _trainings = [];
  int _page = 0, _lastPage = 1, _total = 0, _operation = 0;
  bool _loading = true;
  AssociationRecordsFailure? _failure;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant AssociationRecordList oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.session != widget.session ||
        oldWidget.repository != widget.repository ||
        oldWidget.trainings != widget.trainings) {
      _load();
    }
  }

  Future<void> _load({bool more = false}) async {
    final operation = ++_operation;
    final page = more ? _page + 1 : 1;
    setState(() {
      _loading = true;
      _failure = null;
      if (!more) {
        _members.clear();
        _trainings.clear();
        _page = 0;
        _total = 0;
      }
    });
    try {
      if (widget.session.isDemo ||
          widget.session.association?.isValid != true) {
        throw const AssociationRecordsFailure(
          AssociationRecordsFailureKind.denied,
        );
      }
      final AssociationRecordsPage<Object> result = widget.trainings
          ? await widget.repository.trainings(page: page)
          : await widget.repository.members(page: page);
      if (!mounted || operation != _operation) return;
      if (result.associationId != widget.session.association?.id) {
        throw const AssociationRecordsFailure(
          AssociationRecordsFailureKind.denied,
        );
      }
      if (result.page != page) throw const FormatException('Unexpected page.');
      setState(() {
        if (widget.trainings) {
          _trainings.addAll(result.records.cast<AssociationTraining>());
        } else {
          _members.addAll(result.records.cast<OfficialMember>());
        }
        _page = result.page;
        _lastPage = result.lastPage;
        _total = result.total;
      });
    } catch (error) {
      if (!mounted || operation != _operation) return;
      final failure = error is AssociationRecordsFailure
          ? error
          : const AssociationRecordsFailure(
              AssociationRecordsFailureKind.unavailable,
            );
      setState(() {
        _failure = failure;
        if (failure.kind == AssociationRecordsFailureKind.expired ||
            failure.kind == AssociationRecordsFailureKind.denied) {
          _members.clear();
          _trainings.clear();
          _page = 0;
          _total = 0;
        }
      });
      if (failure.kind == AssociationRecordsFailureKind.expired) {
        widget.onSessionExpired();
      }
    } finally {
      if (mounted && operation == _operation) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.trainings ? 'Training records' : 'Official members';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (!widget.trainings && _page > 0) ...[
          InformationCard(
            child: MetricStrip(values: {'Official members': _total}),
          ),
          const SizedBox(height: 16),
        ],
        Row(
          children: [
            Expanded(
              child: Text(
                title,
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
            IconButton(
              tooltip: 'Refresh $title',
              onPressed: _loading ? null : (widget.onRefresh ?? _load),
              icon: const Icon(Icons.refresh),
            ),
          ],
        ),
        Text(
          widget.trainings
              ? 'Newest dates first. Retained archived history is included.'
              : 'Active registered members, ordered by surname.',
        ),
        const SizedBox(height: 12),
        if (_page > 0 && _total == 0)
          InformationCard(
            child: Text(
              widget.trainings
                  ? 'No training records for your association.'
                  : 'No active official members in your association.',
            ),
          ),
        if (!widget.trainings && _members.isNotEmpty)
          InformationCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (var index = 0; index < _members.length; index++) ...[
                  if (index > 0) const Divider(height: 1),
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    child: Text(_members[index].name),
                  ),
                ],
              ],
            ),
          ),
        for (final training in _trainings)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _TrainingCard(training),
          ),
        if (_failure != null)
          InformationCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  '$title unavailable',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                Text(_failure!.message),
                TextButton(
                  onPressed: _loading ? null : () => _load(more: _page > 0),
                  child: const Text('Retry'),
                ),
              ],
            ),
          ),
        if (_loading)
          Padding(
            padding: const EdgeInsets.all(20),
            child: Center(
              child: CircularProgressIndicator(
                semanticsLabel: 'Loading $title',
              ),
            ),
          ),
        if (_page > 0 && !_loading && _failure == null) ...[
          const SizedBox(height: 12),
          Text(
            'Showing ${widget.trainings ? _trainings.length : _members.length} of $_total',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          if (_page < _lastPage)
            OutlinedButton(
              onPressed: () => _load(more: true),
              child: const Text('Load more'),
            ),
        ],
      ],
    );
  }
}

class _TrainingCard extends StatelessWidget {
  const _TrainingCard(this.training);
  final AssociationTraining training;
  @override
  Widget build(BuildContext context) {
    final start = training.startDate;
    final end = training.endDate;
    final dates = start == null
        ? 'Start date not recorded'
        : displayDate(context, start);
    return InformationCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(training.title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              StatusBadge(status: training.status),
              if (training.isArchived) const StatusBadge(status: 'archived'),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            end != null && end != start
                ? '$dates – ${displayDate(context, end)}'
                : dates,
          ),
          if (training.venue case final venue?) ...[
            const SizedBox(height: 6),
            Text(venue),
          ],
          const SizedBox(height: 6),
          Text(
            'Program year ${training.programYear}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          if (training.conductedBy != null ||
              training.relatedProject != null ||
              training.description != null)
            ExpansionTile(
              tilePadding: EdgeInsets.zero,
              childrenPadding: const EdgeInsets.only(bottom: 8),
              title: const Text('Details'),
              children: [
                if (training.conductedBy case final conductor?)
                  DetailRow(label: 'Conducted by', value: conductor),
                if (training.relatedProject case final project?)
                  DetailRow(label: 'Related project', value: project),
                if (training.description case final description?)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Text(description),
                  ),
              ],
            ),
        ],
      ),
    );
  }
}
