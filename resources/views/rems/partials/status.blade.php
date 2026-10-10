@php
    $raw = is_bool($value) ? ($value ? 'active' : 'inactive') : strtolower(str_replace(' ','_', (string)($value ?? '—')));
    $good = ['active','approved','paid','present','ongoing','completed','yes'];
    $bad = ['rejected','cancelled','terminated','resigned','urgent','absent'];
    $warn = ['pending','in_progress','on_leave','late','partially_paid','high','draft','revision_required','on_hold'];
    $tone = in_array($raw,$good,true) ? 'success' : (in_array($raw,$bad,true) ? 'danger' : (in_array($raw,$warn,true) ? 'warning' : 'neutral'));
@endphp
<span class="status-pill status-{{ $tone }}"><span class="status-dot"></span>{{ str_replace('_',' ',ucwords($raw,'_')) }}</span>
