$folder = "B:\htdocs\video-wall\data\analysis-frames"
$output = "B:\htdocs\video-wall\data\analysis-collage.jpg"
$pidFile = "B:\htdocs\video-wall\data\collage-watcher.pid"

$mutex = New-Object System.Threading.Mutex($false, "Global\DoweVideoWallOllamaCollage")
if (-not $mutex.WaitOne(0, $false)) {
    Write-Host "Ollama collage watcher is already running."
    exit 0
}
Set-Content -LiteralPath $pidFile -Value $PID -Encoding ascii

Add-Type -AssemblyName System.Drawing

$currentViewer = $null
$script:stopRequested = $false
$cancelHandler = [ConsoleCancelEventHandler]{
    param($sender, $eventArgs)
    $eventArgs.Cancel = $true
    $script:stopRequested = $true
    Write-Host ""
    Write-Host "Stopping collage watcher..."
}
[Console]::add_CancelKeyPress($cancelHandler)

function Remove-StaleFrames {
    $cutoff = (Get-Date).AddMinutes(-15)
    Get-ChildItem $folder -Filter *.jpg -File -ErrorAction SilentlyContinue |
        Where-Object { $_.LastWriteTime -lt $cutoff } |
        Remove-Item -Force -ErrorAction SilentlyContinue
}

function New-Collage {
    Remove-StaleFrames
    $files = @(
        Get-ChildItem $folder -Filter *.jpg -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime
    )

    if ($files.Count -eq 0) {
        return $false
    }

    $thumbWidth  = 480
    $thumbHeight = 270
    $padding     = 10

    $columns = [Math]::Min(3, $files.Count)
    $rows = [Math]::Ceiling($files.Count / $columns)

    $canvasWidth  = ($columns * $thumbWidth) +
                    (($columns + 1) * $padding)

    $canvasHeight = ($rows * $thumbHeight) +
                    (($rows + 1) * $padding)

    $bitmap = New-Object System.Drawing.Bitmap(
        $canvasWidth,
        $canvasHeight
    )

    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.Clear([System.Drawing.Color]::Black)

    try {
        for ($i = 0; $i -lt $files.Count; $i++) {

            $image = $null

            try {
                $image = [System.Drawing.Image]::FromFile(
                    $files[$i].FullName
                )

                $column = $i % $columns
                $row = [Math]::Floor($i / $columns)

                $x = $padding +
                     ($column * ($thumbWidth + $padding))

                $y = $padding +
                     ($row * ($thumbHeight + $padding))

                $graphics.DrawImage(
                    $image,
                    $x,
                    $y,
                    $thumbWidth,
                    $thumbHeight
                )
            }
            catch {
                Write-Host "Waiting for frame: $($files[$i].Name)"
            }
            finally {
                if ($image) {
                    $image.Dispose()
                }
            }
        }

        # Save to temporary file first
        $tempOutput = "$output.tmp.jpg"

        $bitmap.Save(
            $tempOutput,
            [System.Drawing.Imaging.ImageFormat]::Jpeg
        )
    }
    finally {
        $graphics.Dispose()
        $bitmap.Dispose()
    }

    Move-Item $tempOutput $output -Force

    # The collage is the durable preview; individual stills are temporary.
    # Remove only frames that are no longer being written, leaving fresh frames
    # available while the active Ollama request is still in progress.
    Remove-StaleFrames

    return $true
}

function Show-Collage {

    if (-not (Test-Path $output)) {
        return
    }

    # Close previous Paint window
    if ($script:currentViewer) {
        try {
            if (-not $script:currentViewer.HasExited) {
                Stop-Process `
                    -Id $script:currentViewer.Id `
                    -Force `
                    -ErrorAction SilentlyContinue

                $script:currentViewer.WaitForExit()
            }
        }
        catch {}
    }

    # Open updated collage
    $script:currentViewer = Start-Process `
        -FilePath "mspaint.exe" `
        -ArgumentList "`"$output`"" `
        -PassThru
}

if (-not (Test-Path $folder)) {
    New-Item `
        -ItemType Directory `
        -Path $folder `
        -Force | Out-Null
}

$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $folder
$watcher.Filter = "*.jpg"
$watcher.NotifyFilter = [System.IO.NotifyFilters]'FileName, LastWrite'
$watcher.EnableRaisingEvents = $true

Write-Host ""
Write-Host "Watching what Ollama sees..."
Write-Host "Frames:  $folder"
Write-Host "Collage: $output"
Write-Host ""
Write-Host "Start an AI analysis in Video Wall."
Write-Host "Press Ctrl+C to stop."
Write-Host ""

try {

    # Display existing frames if there are any
    if (New-Collage) {
        Show-Collage
    }

    while (-not $script:stopRequested) {

        $change = $watcher.WaitForChanged(
            [System.IO.WatcherChangeTypes]::Created,
            1000
        )

        if ($change.TimedOut) {
            continue
        }

        Write-Host "New Ollama frame: $($change.Name)"

        # Give FFmpeg time to finish writing it
        Start-Sleep -Milliseconds 600

        if (New-Collage) {

            Write-Host "Updating collage..."

            Show-Collage
        }
    }
}
finally {

    $watcher.Dispose()
    [Console]::remove_CancelKeyPress($cancelHandler)

    if ($currentViewer) {
        try {
            if (-not $currentViewer.HasExited) {
                Stop-Process `
                    -Id $currentViewer.Id `
                    -Force `
                    -ErrorAction SilentlyContinue
            }
        }
        catch {}
    }
    $mutex.ReleaseMutex()
    $mutex.Dispose()
    Remove-Item -LiteralPath $pidFile -Force -ErrorAction SilentlyContinue
}
