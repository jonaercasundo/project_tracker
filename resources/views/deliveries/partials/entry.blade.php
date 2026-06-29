<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Loading...</title>
</head>
<body>

<script>
const id = "{{ $id }}";
const delivery_id = "{{ $delivery_id }}";

// ALWAYS redirect to scan first
window.location.replace(`/scan/${id}?delivery_id=${delivery_id}`);
</script>

Redirecting...

</body>
</html>