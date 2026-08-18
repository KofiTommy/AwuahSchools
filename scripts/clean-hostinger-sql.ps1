param(
    [Parameter(Mandatory = $true)][string]$Source,
    [Parameter(Mandatory = $true)][string]$Destination
)

$sql = Get-Content -LiteralPath $Source -Raw

# Shared Hostinger imports cannot contain stored-program delimiter blocks.
$sql = [regex]::Replace($sql, '(?ms)^\s*DELIMITER\s+\S+\s*\r?\n.*?^\s*DELIMITER\s*;\s*$', '')

# Remove server-specific ownership and administrative statements.
$sql = [regex]::Replace($sql, '(?im)^.*\bDEFINER\s*=.*(?:\r?\n|$)', '')
$sql = [regex]::Replace($sql, '(?im)^\s*(CREATE|DROP)\s+DATABASE\b.*?;\s*$', '')
$sql = [regex]::Replace($sql, '(?im)^\s*(GRANT|CREATE\s+USER|FLUSH\s+PRIVILEGES)\b.*?;\s*$', '')

Set-Content -LiteralPath $Destination -Value $sql -NoNewline
