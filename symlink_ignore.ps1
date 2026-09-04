$root = (Get-Location).Path

$existing = if (Test-Path .gitignore) {
    Get-Content .gitignore
} else {
    @()
}

$links = Get-ChildItem -Path . -Recurse -Force -Attributes ReparsePoint |
    ForEach-Object {
        $_.FullName.Substring($root.Length + 1).Replace('\','/')
    }

($existing + $links) |
    Where-Object { $_.Trim() -ne '' } |
    Sort-Object -Unique |
    Set-Content .gitignore