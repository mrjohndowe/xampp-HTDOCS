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




Write-Wait "This script will ignore all symlinks in the current directory and its subdirectories, and remove them from Git tracking." -Seconds 2
Write-Wait "Press any key to continue or Ctrl+C to cancel."
[void][System.Console]::ReadKey($true)
Write-Wait "Processing symlinks..." -Seconds 2
$root = (Get-Location).Path
Write-Wait "Root directory: $root" -Seconds 1

Write-Wait "Finding symlinks..." -Seconds 1
Write-Wait "Updating .gitignore..." -Seconds 1
Write-Wait "Removing symlinks from Git tracking..." -Seconds 1
$existing = if (Test-Path .gitignore) {
    Get-Content .gitignore
} else {
    @()
}
Write-Wait "Existing .gitignore entries: $($existing.Count)" -Seconds 1
$links = Get-ChildItem -Path . -Recurse -Force -Attributes ReparsePoint |
    ForEach-Object {
        $_.FullName.Substring($root.Length + 1).Replace('\','/')
    }
Write-Wait "Found symlinks: $($links.Count)" -Seconds 1
Write-Wait "Updating .gitignore with symlinks..." -Seconds 1
($existing + $links) |
    Where-Object { $_.Trim() -ne '' } |
    Sort-Object -Unique |
    Set-Content .gitignore
Write-Wait "Updated .gitignore with symlinks." -Seconds 1
Write-Wait "Removing symlinks from Git tracking..." -Seconds 1
Get-Content .gitignore |
    Where-Object { $_.Trim() -ne '' -and -not $_.StartsWith('#') } |
    ForEach-Object {
        git rm -r --cached -- $_
    }
Write-Wait "Removed symlinks from Git tracking." -Seconds 1
Write-Wait "Done! You can now commit the changes to .gitignore and the removal of symlinks from Git tracking." -Seconds 2
Write-Wait "Press any key to continue or Ctrl+C to cancel."
[void][System.Console]::ReadKey($true)
Write-Wait "Committing changes..." -Seconds 1
Write-Wait "Checking the status of the repository" -Seconds 1
git status
Write-Wait "Adding .gitignore to the staging area..." -Seconds 1
git add .gitignore
Write-Wait "Committing changes..." -Seconds 1
git commit -m "Ignore symlinks and remove them from Git tracking"
Write-Wait "Changes committed. You can now push the changes to the remote repository." -Seconds 2
git push -u origin main
Write-Wait "Changes pushed to the remote repository." -Seconds 2
Write-Wait "All done! The symlinks have been ignored and removed from Git tracking." -Seconds 2
Write-Wait "Press any key to exit."
[void][System.Console]::ReadKey($true)


