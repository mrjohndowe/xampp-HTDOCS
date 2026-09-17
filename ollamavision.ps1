$folder = "B:\xampp\htdocs\video-wall\data\analysis-frames"

$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $folder
$watcher.Filter = "*.jpg"
$watcher.NotifyFilter = [System.IO.NotifyFilters]'FileName, LastWrite'
$watcher.EnableRaisingEvents = $true

$currentViewer = $null

Write-Host "Ollama Vision is watching for new frames in the folder:"
Write-Host "Folder: $folder"
Write-Host "Press Ctrl+C to stop."
Write-Host ""

try {
    while ($true) {

        $change = $watcher.WaitForChanged(
            [System.IO.WatcherChangeTypes]::Created
        )

        $file = Join-Path $folder $change.Name

        # Wait until FFmpeg has actually finished writing enough of the image
        for ($i = 0; $i -lt 50; $i++) {
            if ((Test-Path $file) -and ((Get-Item $file).Length -gt 0)) {
                break
            }

            Start-Sleep -Milliseconds 100
        }

        if (-not (Test-Path $file)) {
            continue
        }

        # Close the previous image
        if ($currentViewer -and -not $currentViewer.HasExited) {
            Stop-Process -Id $currentViewer.Id -Force -ErrorAction SilentlyContinue
            $currentViewer.WaitForExit()
        }

        Write-Host "Ollama sees: $change.Name"

        # Open this frame
        $currentViewer = Start-Process `
            -FilePath "mspaint.exe" `
            -ArgumentList "`"$file`"" `
            -PassThru
    }
}
finally {
    if ($currentViewer -and -not $currentViewer.HasExited) {
        Stop-Process -Id $currentViewer.Id -Force -ErrorAction SilentlyContinue
    }

    $watcher.Dispose()
}
