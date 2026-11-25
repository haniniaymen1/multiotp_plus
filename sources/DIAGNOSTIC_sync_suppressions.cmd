@echo off
setlocal enableextensions
REM =====================================================================
REM  DIAGNOSTIC SYNC SUPPRESSIONS v1.3 - multiOTP Plus
REM  Cause racine (2026-11-25) : multiotp.exe execute php\multiotp.windows.php
REM  (monolithe avec class embarque). Le fichier determinant pour le bouton
REM  Sync est donc C:\multiotp\php\multiotp.windows.php - verifie en section 2.
REM  A executer sur la machine ou tourne la synchronisation. Double-clic
REM  suffit ; aucune modification n'est faite. Rapport :
REM  C:\multiotp\diagnostic_sync.txt
REM =====================================================================
if not exist C:\multiotp\nul (
  echo ERREUR: C:\multiotp introuvable sur cette machine.
  echo Ce script doit etre lance sur la machine multiOTP elle-meme.
  pause
  exit /b 1
)
set LOGF=C:\multiotp\diagnostic_sync.txt
echo ===== DIAGNOSTIC SYNC SUPPRESSIONS %date% %time% =====> "%LOGF%"

call :log "=== 1. MACHINE ==="
for /f "delims=" %%H in ('hostname') do call :log "hostname : %%H"

call :log ""
call :log "=== 2. MONOLITHE php\multiotp.windows.php (LE fichier du bouton Sync) ==="
if exist C:\multiotp\php\multiotp.windows.php (
  call :log "[OK] fichier present"
  for %%F in (C:\multiotp\php\multiotp.windows.php) do call :log "taille = %%~zF octets (patche v4.0.11 attendu 3236283)"
  findstr /C:"v4.0.11 AD-DELETE-SYNC" C:\multiotp\php\multiotp.windows.php >nul 2>&1
  if errorlevel 1 (call :log "[NON] garde v4.0.11 ABSENTE -> le bouton Sync ne supprimera pas : deployer le monolithe patche du package v1.3") else (call :log "[OK] garde v4.0.11 AD-DELETE-SYNC presente")
  call :log "MD5 attendu (patche v4.0.11) : 5bc34fcb5cab529631f76963583e61bc"
  certutil -hashfile C:\multiotp\php\multiotp.windows.php MD5 | findstr /I /V "certutil hash"
) else (
  call :log "[??] C:\multiotp\php\multiotp.windows.php ABSENT - installation non standard"
)

call :log ""
call :log "=== 3. CLASS RACINE multiotp.class.php (utilise par la console web) ==="
findstr /C:"ldap_total_counter >= 1" C:\multiotp\multiotp.class.php >nul 2>&1
if errorlevel 1 (call :log "[NON] garde absente -> class racine OFFICIEL") else (call :log "[OK] garde AD-DELETE-SYNC presente")
findstr /C:"$delete_old_synced_users = TRUE" C:\multiotp\multiotp.class.php >nul 2>&1
if errorlevel 1 (call :log "[NON] defaut FALSE") else (call :log "[OK] defaut TRUE")
call :log "MD5 attendu (patche v4.0.9) : e99ed9db32b203989656dfb217a1f418"
certutil -hashfile C:\multiotp\multiotp.class.php MD5 | findstr /I /V "certutil hash"

call :log ""
call :log "=== 4. SERVEUR ENRICHI v4.0.10 ==="
findstr /C:"ha_class_patched" C:\multiotp\multiotp.server.php >nul 2>&1
if errorlevel 1 (call :log "[NON] serveur officiel ou ancien") else (call :log "[OK] serveur v4.0.10")

call :log ""
call :log "=== 5. CONTRIBUT ==="
for /f %%N in ('dir /b C:\multiotp\contrib\*.* 2^>nul ^| find /c /v ""') do call :log "fichiers dans contrib : %%N (attendu 37)"

call :log ""
call :log "=== 6. COPIES DE multiotp.class.php SOUS C:\multiotp ==="
for /f "delims=" %%F in ('dir /b /s C:\multiotp\multiotp.class.php 2^>nul') do call :log "  %%F"

call :log ""
call :log "=== 7. multiotp.exe (278000 octets = lanceur officiel : il execute php\multiotp.windows.php) ==="
for %%F in (C:\multiotp\multiotp.exe) do call :log "taille multiotp.exe = %%~zF octets"

call :log ""
call :log "=== 8. CONTENU DE C:\multiotp\sync.bat ==="
if exist C:\multiotp\sync.bat (type C:\multiotp\sync.bat) else (call :log "[??] sync.bat absent - il sera recree au prochain clic sur Sync")

call :log ""
call :log "=== 9. PHP ==="
if exist C:\multiotp\php\php.exe (call :log "[OK] C:\multiotp\php\php.exe present") else (call :log "[??] C:\multiotp\php\php.exe ABSENT")

call :log ""
call :log "=== 10. COMPTES ahmed ENCORE SUR DISQUE ==="
for /f "delims=" %%U in ('dir /b C:\multiotp\users 2^>nul ^| findstr /I "ahmed"') do call :log "  users\%%U"
for /f "delims=" %%U in ('dir /b C:\multiotp\users_advanced 2^>nul ^| findstr /I "ahmed"') do call :log "  users_advanced\%%U"
call :log "(avec le monolithe patche, ces comptes seront SUPPRIMES a la prochaine passe)"

call :log ""
call :log "=== 11. TACHE PLANIFIEE (app) ==="
schtasks /query /fo LIST /v /tn "Sincronizar_usuarios_LDAP" > "%TEMP%\task_sync.txt" 2>&1
if errorlevel 1 (call :log "[??] tache Sincronizar_usuarios_LDAP introuvable") else (
  findstr /I "TaskName " "%TEMP%\task_sync.txt"
  findstr /I "Task To Run" "%TEMP%\task_sync.txt"
  findstr /I "Statut Status" "%TEMP%\task_sync.txt"
  type "%TEMP%\task_sync.txt" >> "%LOGF%"
)

call :log ""
call :log "===== FIN DU DIAGNOSTIC - rapport complet : %LOGF% ====="
call :log "ENVOYEZ CE RAPPORT si le probleme persiste."
echo.
echo Rapport complet enregistre dans %LOGF%
pause
exit /b 0

:log
echo %~1
>>"%LOGF%" echo %~1
goto :eof
