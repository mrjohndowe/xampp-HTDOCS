<#
.SYNOPSIS
Commits the specified files and synchronizes the current branch before pushing.
.EXAMPLE
& 'B:\htdocs\gitPush.ps1' -Files @('README.md', 'app/lang.php') -Message 'Update site languages'
.EXAMPLE
& 'B:\htdocs\gitPush.ps1' -ProjectPath 'B:\htdocs\lifeTools'
#>
[CmdletBinding()]
param(
    [string]$ProjectPath,
    [string[]]$Files,
    [string]$Message,
    [switch]$Resume
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Color {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)]
        [string]$Message,

        [ValidateSet("Black", "DarkBlue", "DarkGreen", "DarkCyan", "DarkRed", "DarkMagenta", "DarkYellow", "Gray", "Blue", "Green", "Cyan", "Red", "Magenta", "Yellow", "White")]
        [ConsoleColor]$ForegroundColor = "DarkGreen",

        [ValidateSet("Black", "DarkBlue", "DarkGreen", "DarkCyan", "DarkRed", "DarkMagenta", "DarkYellow", "Gray", "Blue", "Green", "Cyan", "Red", "Magenta", "Yellow", "White")]
        [ConsoleColor]$BackgroundColor = "Black",

        [bool]$UpperCase
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


function pushGit {
    param(
        [Parameter(Mandatory)]
        [string]$ProjectPath,
        [string[]]$Files,
        [string]$Message,
        [switch]$Resume
    )

    if (-not $Resume -and (-not $Files -or [string]::IsNullOrWhiteSpace($Message) -or
        @($Files | Where-Object { [string]::IsNullOrWhiteSpace($_) -or $_ -in '.', './', '.\' }).Count)) {
        throw 'Provide specific file paths and a commit message.'
    }

    $folder = (Resolve-Path -LiteralPath $ProjectPath).ProviderPath
    $branch = git -C $folder symbolic-ref --quiet --short HEAD
    if ($LASTEXITCODE -ne 0) {
        throw 'Open a Git repository with a checked-out branch.'
    }

    $remote = git -C $folder config --get "branch.$branch.remote"
    if ($LASTEXITCODE -eq 1) {
        $remote = 'origin'
    } elseif ($LASTEXITCODE -ne 0) {
        throw 'Could not determine the Git remote.'
    }

    $trackedStatus = git -C $folder status --porcelain=v1 --untracked-files=no
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not check the staging area.'
    }
    if ($trackedStatus -cmatch '^[^ ]') {
        throw 'The repository already has staged or conflicted files. Resolve those before running this script.'
    }

    git -C $folder ls-remote --exit-code --heads $remote "refs/heads/$branch" | Out-Null
    if ($LASTEXITCODE -eq 2) {
        throw "Create the $branch branch on $remote first. The script rebases against that branch before pushing."
    }
    if ($LASTEXITCODE -ne 0) {
        throw "Could not verify the $branch branch on $remote."
    }

    if (-not $Resume) {
        git --literal-pathspecs -C $folder add -- @Files
        if ($LASTEXITCODE -ne 0) {
            throw 'Git could not stage the requested files.'
        }

        $messageFile = [IO.Path]::GetTempFileName()
        try {
            [IO.File]::WriteAllText($messageFile, $Message)
            git --literal-pathspecs -C $folder commit --only --file $messageFile -- @Files
            if ($LASTEXITCODE -ne 0) {
                throw 'Git could not commit the requested files.'
            }
        } finally {
            Remove-Item -LiteralPath $messageFile -Force
        }
    }

    git -C $folder pull --rebase --autostash -- $remote $branch
    if ($LASTEXITCODE -ne 0) {
        throw "Could not synchronize $branch with $remote. The commit is local."
    }
    $conflicts = git -C $folder ls-files --unmerged
    if ($LASTEXITCODE -ne 0 -or $conflicts) {
        throw 'Check for conflicts after synchronization. The commit is local.'
    }

    git -C $folder push -- $remote $branch
    if ($LASTEXITCODE -ne 0) {
        throw "Could not push $branch to $remote."
    }
}

if ($Resume) {
    if ($PSBoundParameters.ContainsKey('Files') -or $PSBoundParameters.ContainsKey('Message')) {
        throw 'Use -Resume by itself or with -ProjectPath.'
    }
    if (-not $ProjectPath) {
        $ProjectPath = (Get-Location).ProviderPath
    }
    pushGit -ProjectPath $ProjectPath -Resume
    return
}

if ($PSBoundParameters.ContainsKey('Files') -or $PSBoundParameters.ContainsKey('Message')) {
    if (-not $Files -or [string]::IsNullOrWhiteSpace($Message)) {
        throw 'Supply both -Files and -Message.'
    }
    if (-not $ProjectPath) {
        $ProjectPath = (Get-Location).ProviderPath
    }
    pushGit -ProjectPath $ProjectPath -Files $Files -Message $Message
    return
}

Clear-Host
Create-Banner

if (-not $ProjectPath) {
    $ProjectPath = Read-Host 'Enter the folder path you would like to push'
}
if (-not (Test-Path -LiteralPath $ProjectPath -PathType Container)) {
    Write-Color "Folder not found: $ProjectPath" Black Red
    return
}

$Files = @()
while ($file = Read-Host 'Enter one file path, or press Enter when finished') {
    $Files += $file
}
if (-not $Files) {
    return
}

$Message = Read-Host 'Enter the commit message'
pushGit -ProjectPath $ProjectPath -Files $Files -Message $Message
Set-Location -LiteralPath $ProjectPath
(Get-Location).ProviderPath
