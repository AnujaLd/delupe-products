Param(
    [int]$TimeoutSeconds = 120
)

Write-Host "Starting Docker Compose (build + detach)..."
docker-compose up -d --build

$start = Get-Date
Write-Host "Waiting for Postgres to be ready on db:5432..."

while ( ((Get-Date) - $start).TotalSeconds -lt $TimeoutSeconds ) {
    try {
        $tcp = New-Object System.Net.Sockets.TcpClient
        $async = $tcp.BeginConnect('127.0.0.1', 5432, $null, $null)
        $wait = $async.AsyncWaitHandle.WaitOne(500)
        if ($wait -and $tcp.Connected) {
            $tcp.EndConnect($async)
            $tcp.Close()
            Write-Host "Postgres appears to be listening (127.0.0.1:5432)."
            break
        }
    } catch {
        # ignore
    }
    Start-Sleep -Seconds 1
}

if ( ((Get-Date) - $start).TotalSeconds -ge $TimeoutSeconds ) {
    Write-Host "Timed out waiting for Postgres. You may need to run migrations manually." -ForegroundColor Yellow
} else {
    Write-Host "Installing Composer dependencies inside container (if needed)..."
    docker exec -w /var/www delupe_app composer install --no-interaction --prefer-dist

    Write-Host "Generating app key..."
    docker exec -w /var/www delupe_app php artisan key:generate --ansi

    Write-Host "Running migrations..."
    docker exec -w /var/www delupe_app php artisan migrate --force --no-interaction

    Write-Host "Importing sample products.json..."
    docker exec -w /var/www delupe_app php artisan app:import-products /var/www/products.json

    Write-Host "All done. App should be available at http://localhost:8080"
}
