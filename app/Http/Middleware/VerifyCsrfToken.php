protected $except = [
    'api/*',  // Exclude all API routes from CSRF verification
    '*',      // Temporarily disable all CSRF protection for debugging
];
