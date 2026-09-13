$folder = "B:\xampp\htdocs\video-wall\data\analysis-frames"
$output = "B:\xampp\htdocs\video-wall\data\analysis-collage.jpg"

Add-Type -AssemblyName System.Drawing

$currentViewer = $null

function New-Collage {
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

    while ($true) {

        $change = $watcher.WaitForChanged(
            [System.IO.WatcherChangeTypes]::Created
        )

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
}
