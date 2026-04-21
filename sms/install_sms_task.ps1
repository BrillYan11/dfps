# Get the directory where this script is located
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
$VbsPath = Join-Path $ScriptPath "run_sms_server_hidden.vbs"

# Define Task Name
$TaskName = "DA SMS Gateway"

# Check if task already exists and delete it to refresh
$existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    Write-Host "Updating existing task..." -ForegroundColor Cyan
}

# Create the Task Action (running the VBS script)
# Using wscript.exe to execute the VBS file
$Action = New-ScheduledTaskAction -Execute "wscript.exe" -Argument "`"$VbsPath`"" -WorkingDirectory $ScriptPath

# Create the Task Trigger (At Log On)
$Trigger = New-ScheduledTaskTrigger -AtLogOn

# Create the Task Settings
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Days 365)

# Register the Task
Register-ScheduledTask -Action $Action -Trigger $Trigger -Settings $Settings -TaskName $TaskName -Description "Runs the DA SMS Gateway Node.js server in the background at logon." -RunLevel Highest

Write-Host "------------------------------------------------" -ForegroundColor Green
Write-Host "SUCCESS: Task '$TaskName' has been created!" -ForegroundColor Green
Write-Host "The SMS Server will now start automatically every time you log in." -ForegroundColor White
Write-Host "It will run silently in the background (hidden)." -ForegroundColor White
Write-Host "------------------------------------------------" -ForegroundColor Green
