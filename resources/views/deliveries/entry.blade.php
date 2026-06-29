<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Loading...</title>

  <script>
    const id = "{{ $id }}";
    const delivery_id = "{{ $delivery_id }}";

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/philgeps/sw.js')
        .then(reg => console.log('Service Worker registered:', reg.scope))
        .catch(err => console.error('Service Worker failed:', err));
    }

    if (navigator.onLine) {
      window.location.href = `/scan?id=${id}&delivery_id=${delivery_id}`;
    } else {
      window.location.href = `/offline-scan?id=${id}&delivery_id=${delivery_id}`;
    }
  </script>
</head>
<body>
  Redirecting...
</body>
</html>