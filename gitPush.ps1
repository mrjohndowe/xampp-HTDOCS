[CmdletBinding()]
param(
    [string]$projectPath
)

Set-StricMode -Version -Latest
$ErrorActionPreference = "Stop"

function Write-Color {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)]
        [string]$Message,

        [ValidateSet("Black", "DarkBlue", "DarkGreen", "DarkCyan", "DarkRed", "DarkMagenta", "DarkYellow", "Gray", "Blue", "Green", "Cyan", "Red", "Magenta", "Yellow", "White")]
        [ConsoleColor]$ForegroundColor = "DarkGreen",

        [ValidateSet("Black", "DarkBlue", "DarkGreen", "DarkCyan", "DarkRed", "DarkMagenta", "DarkYellow", "Gray", "Blue", "Green", "Cyan", "Red", "Magenta", "Yellow", "White")]
        [ConsoleColor]$BackgroundColor = "Black",

        [boolean]$UpperCase
    )

    if ($UpperCase) {
        $Message = $Message.ToUpperInvariant()
    }

    Write-Host $Message `
        -ForegroundColor $ForegroundColor `
        -BackgroundColor $BackgroundColor
    [Console]::ResetColor()
}

function Write-Wait {
    param(
        [string]$Message = "Waiting",
        [int]$Seconds = 3
    )

    for ($i = $Seconds; $i -ge 1; $i--) {
        Write-Host "`r$Message... $i " -NoNewline
        Start-Sleep -Seconds 1
    }

    Write-Host "`r$Message... Done!    "
}


function Write-ColorWait {
    param(
        [string]$Message = "Waiting",
        [ConsoleColor]$ForegroundColor = "White",
        [ConsoleColor]$BackgroundColor = "Black",
        [bool]$UpperCase = $false,
        [int]$Seconds = 5
    )

    if ($UpperCase) {
        $Message = $Message.ToUpperInvariant()
    }

    for ($i = $Seconds; $i -ge 1; $i--) {
        Write-Host "`r$Message... $i " -NoNewline `
            -ForegroundColor $ForegroundColor `
            -BackgroundColor $BackgroundColor

        Start-Sleep -Seconds 1
    }

    Write-Host "`r$Message... Done!    " `
        -ForegroundColor $ForegroundColor `
        -BackgroundColor $BackgroundColor

    [Console]::ResetColor()
}

function Create-Banner {
    $palette = @(
        [ConsoleColor]::DarkCyan,
        [ConsoleColor]::Cyan,
        [ConsoleColor]::Blue,
        [ConsoleColor]::DarkBlue,
        [ConsoleColor]::White,
        [ConsoleColor]::Red
    )
    $banner = @'
  _// //                             _//                     _//                  _//             _// _//
_//    _//               _/          _//                     _//                  _//             _// _//
 _//         _///_/ _///   _/ _//  _/_/ _/ _////             _//_// _//   _//// _/_/ _/   _//     _// _//   _//    _/ _///
   _//     _//    _//   _//_/  _//   _//  _//                _// _//  _//_//      _//   _//  _//  _// _// _/   _//  _//
      _// _//     _//   _//_/   _//  _//    _///             _// _//  _//  _///   _//  _//   _//  _// _//_///// _// _//
_//    _// _//    _//   _//_// _//   _//      _//            _// _//  _//    _//  _//  _//   _//  _// _//_/         _//
  _// //     _///_///   _//_//        _// _// _//            _//_///  _//_// _//   _//   _// _///_///_///  _////   _///
                           _//
'@ -split "`r?`n"

    $width = ($banner | Measure-Object -Property Length -Maximum).Maximum
    $bandWidth = [Math]::Max(1, [Math]::Ceiling($width / $palette.Count))
    Write-Host ('=' * $width) -ForegroundColor Cyan
    foreach ($line in $banner) {
        for ($position = 0; $position -lt $line.Length; $position++) {
            if ([char]::IsWhiteSpace($line[$position])) {
                Write-Host $line[$position] -NoNewline
                continue
            }
            $paletteIndex = [Math]::Min(
                [Math]::Floor($position / $bandWidth),
                $palette.Count - 1
            )
            Write-Host $line[$position] -NoNewline -ForegroundColor $palette[$paletteIndex]
        }
        Write-Host ""
    }
    Write-Host ('=' * $width) -ForegroundColor Cyan
    $title = " Installation Menu "
    $padding = [Math]::Max(0, $width - $title.Length)
    Write-Host (('=' * [Math]::Floor($padding / 2)) + $title + ('=' * [Math]::Ceiling($padding / 2))) `
        -ForegroundColor Green
    [Console]::ResetColor()
    Write-Host ""
}


# $continueRunning = $true


function pushGit($projectPath){


    Set-Location -LiteralPath $projectPath 
    $files = "."
    $message = "Build LifeTools Personal Planner"
    git add $files
    git commit -m $message
    git pull --rebase origin main
    if($LASTEXITCODE -eq 0){
        git push origin main
    }
    Set-Location ../

}


Clear-Host
Create-Banner

$customPath = Read-Host "Enter the folder path you would like to push"
if(Test-Path -LiteralPath $customPath -PathType Container){
    pushGit($customPath)
    Set-Location -LiteralPath $customPath    
    return (Resolve-Path -LiteralPath $customPath).Path
   
}
Write-Color "Folder not found: $customPath" Black Red





