Kraftrack contact form
======================

Name:  {{ $payload['name'] }}
Email: {{ $payload['email'] }}
Phone: {{ $payload['phone'] ?: '—' }}
Topic: {{ $payload['topic'] }}

Message
-------
{{ $payload['message'] }}
