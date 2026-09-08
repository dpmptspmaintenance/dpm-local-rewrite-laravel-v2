<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Google</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Optional: Style the error message */
    .error-popup {
      color: #721c24;
      /* Dark red text */
      background-color: #f8d7da;
      /* Light red background */
      border: 1px solid #f5c6cb;
      /* Red border */
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
      text-align: center;
      /* Adding a transition for a smooth fade-out effect */
      transition: opacity 0.5s ease-out;
    }
  </style>
</head>

<body class="bg-light d-flex align-items-center" style="height:100vh;">

  <div class="container text-center">
    @if (request()->has('error'))
      <div id="error-message" class="error-popup mx-auto" style="max-width:400px;">{{ request('error') }}</div>
    @endif

    <div class="card shadow mx-auto" style="max-width:400px;">
      <div class="card-body p-4">
        <h4 class="mb-3">Login dengan Google</h4>
        <a href="{{ route('auth.google') }}" class="btn btn-danger w-100">
          <i class="bi bi-google"></i> Login via Google
        </a>
      </div>
    </div>
  </div>

  <script>
    // 3. Find the error message element by its ID
    const errorMessageElement = document.getElementById('error-message');

    // Check if the error message element actually exists on the page
    if (errorMessageElement) {
      // 4. Set a timer to hide the element after 5 seconds (5000 milliseconds)
      setTimeout(() => {
        errorMessageElement.style.opacity = '0'; // Start the fade-out
        // Wait for the fade-out to finish before hiding it completely
        setTimeout(() => {
          errorMessageElement.style.display = 'none';
        }, 500); // 0.5 second transition
      }, 5000); // 5-second delay before starting fade
    }
  </script>
</body>

</html>
