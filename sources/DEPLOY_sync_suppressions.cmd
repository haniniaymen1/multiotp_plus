@echo off
setlocal enableextensions
REM =====================================================================
REM  DEPLOIEMENT VERIFIE + SYNC FORCEE - multiOTP Plus v1.3
REM  Remplace :
REM    - C:\multiotp\php\multiotp.windows.php  (MONOLITHE = moteur du
REM      bouton Sync / tache planifiee / multiotp.exe) par la version
REM      patchee v4.0.11 AD-DELETE-SYNC  <- LA CORRECTION PRINCIPALE
REM    - C:\multiotp\multiotp.class.php        (console web, v4.0.9)
REM    - C:\multiotp\multiotp.server.php       (console web, v4.0.10)
REM    - C:\multiotp\contrib\                  (37 fichiers)
REM  Puis RE-verifie tout sur disque (gardes + MD5) et lance UNE
REM  synchronisation FORCEE avec log en direct.
REM
REM  NOUVEAU v1.2+ : si le script est lance depuis C:\multiotp lui-meme,
REM  il ne tente pas de copier sur lui-meme : MODE VERIFICATION + SYNC.
REM  Pour un remplacement complet : extrayez le zip ailleurs (ex.
REM  C:\Temp\pkg) et lancez le script depuis CE dossier, ou passez le
REM  chemin du package en argument.
REM =====================================================================
if not exist C:\multiotp\nul (
  echo ERREUR: C:\multiotp introuvable sur cette machine.
  pause
  exit /b 1
)
net session >nul 2>&1
if errorlevel 1 (
  echo ERREUR: droits administrateur requis. Clic droit sur ce fichier,
  echo puis "Executer en tant qu'administrateur".
  pause
  exit /b 1
)
set "PKG=%~dp0"
if not "%~1"=="" set "PKG=%~1"
if "%PKG:~-1%"=="\" set "PKG=%PKG:~0,-1%"
echo Package utilise : %PKG%
echo Machine locale  :
hostname

set "VERIFY_ONLY=0"
if /I "%PKG%"=="C:\multiotp" set "VERIFY_ONLY=1"
if "%VERIFY_ONLY%"=="1" goto VERIFY_MODE

echo.
echo === ETAPE 1 : controle du package ===
if not exist "%PKG%\php\multiotp.windows.php" (
  echo ERREUR: %PKG%\php\multiotp.windows.php introuvable.
  echo Extrayez le zip v1.3 dans un dossier dedie, par exemple C:\Temp\pkg,
  echo puis relancez ce script depuis ce dossier - ou passez le chemin en
  echo argument : DEPLOY_sync_suppressions.cmd C:\Temp\pkg\MultiOTP_Sync_Suppressions_v1.3
  pause
  exit /b 1
)
findstr /C:"v4.0.11 AD-DELETE-SYNC" "%PKG%\php\multiotp.windows.php" >nul 2>&1
if errorlevel 1 (
  echo ERREUR: le multiotp.windows.php du package n'a PAS la garde v4.0.11. Arret.
  pause
  exit /b 1
)
echo [OK] le package contient le monolithe patche v4.0.11 (moteur du bouton Sync)
if exist "%PKG%\multiotp.class.php" (
  findstr /C:"ldap_total_counter >= 1" "%PKG%\multiotp.class.php" >nul 2>&1
  if errorlevel 1 (
    echo ERREUR: le multiotp.class.php du package n'a pas la garde. Arret.
    pause
    exit /b 1
  )
  echo [OK] le package contient le class racine patche v4.0.9
)

echo.
echo === ETAPE 2 : sauvegarde de l'existant ===
set "TS=%date%_%time%"
set "TS=%TS:/=-%"
set "TS=%TS::=-%"
set "TS=%TS: =_%"
set "BK=C:\multiotp\backup\deploiement_%TS%"
mkdir "%BK%" 2>nul
mkdir "%BK%\php" 2>nul
copy /Y C:\multiotp\multiotp.class.php  "%BK%\multiotp.class.php"  >nul
copy /Y C:\multiotp\multiotp.server.php "%BK%\multiotp.server.php" >nul
if exist C:\multiotp\php\multiotp.windows.php copy /Y C:\multiotp\php\multiotp.windows.php "%BK%\php\multiotp.windows.php" >nul
echo Sauvegarde dans : %BK%

echo.
echo === ETAPE 3 : remplacement - monolithe + class + server + contrib ===
copy /Y "%PKG%\php\multiotp.windows.php" C:\multiotp\php\multiotp.windows.php
if errorlevel 1 (
  echo ERREUR de copie du monolithe - fichier verrouille ou droits insuffisants.
  pause
  exit /b 1
)
echo [OK] monolithe remplace (C:\multiotp\php\multiotp.windows.php)
copy /Y "%PKG%\multiotp.class.php"  C:\multiotp\multiotp.class.php  >nul
copy /Y "%PKG%\multiotp.server.php" C:\multiotp\multiotp.server.php >nul
xcopy /E /I /Y "%PKG%\contrib" C:\multiotp\contrib >nul
for /f %%N in ('dir /b C:\multiotp\contrib\*.* 2^>nul ^| find /c /v ""') do echo contrib : %%N fichiers - attendu 37

echo.
echo === ETAPE 4 : verification SUR DISQUE apres remplacement ===
findstr /C:"v4.0.11 AD-DELETE-SYNC" C:\multiotp\php\multiotp.windows.php >nul 2>&1
if errorlevel 1 (
  echo ECHEC CRITIQUE : le monolithe copie n'a pas la garde. Restaurez :
  echo   copy /Y "%BK%\php\multiotp.windows.php" C:\multiotp\php\multiotp.windows.php
  pause
  exit /b 1
)
echo [OK] garde v4.0.11 presente dans le monolithe
call :log "MD5 monolithe attendu 5bc34fcb5cab529631f76963583e61bc :"
certutil -hashfile C:\multiotp\php\multiotp.windows.php MD5 | findstr /I /V "certutil hash"
findstr /C:"ldap_total_counter >= 1" C:\multiotp\multiotp.class.php >nul 2>&1
if errorlevel 1 (echo [ATTENTION] garde absente du class racine) else (echo [OK] garde presente dans le class racine)
call :log "MD5 class racine attendu e99ed9db32b203989656dfb217a1f418 :"
certutil -hashfile C:\multiotp\multiotp.class.php MD5 | findstr /I /V "certutil hash"
goto SYNC_NOW

:VERIFY_MODE
echo.
echo MODE VERIFICATION : le script est dans C:\multiotp lui-meme, donc
echo AUCUNE copie n'est tentee - les fichiers en place sont verifies tel quel.
findstr /C:"v4.0.11 AD-DELETE-SYNC" C:\multiotp\php\multiotp.windows.php >nul 2>&1
if errorlevel 1 (
  echo [NON] garde v4.0.11 ABSENTE du monolithe : il faut le remplacer.
  echo Extrayez le package v1.3 dans C:\Temp\pkg puis relancez ce script
  echo depuis ce dossier - ou passez le chemin du dossier en argument.
  pause
  exit /b 1
)
echo [OK] garde v4.0.11 presente dans le monolithe
call :log "MD5 monolithe attendu 5bc34fcb5cab529631f76963583e61bc :"
certutil -hashfile C:\multiotp\php\multiotp.windows.php MD5 | findstr /I /V "certutil hash"
findstr /C:"ldap_total_counter >= 1" C:\multiotp\multiotp.class.php >nul 2>&1
if errorlevel 1 (echo [NON] garde absente du class racine) else (echo [OK] garde presente dans le class racine)
call :log "MD5 class racine attendu e99ed9db32b203989656dfb217a1f418 :"
certutil -hashfile C:\multiotp\multiotp.class.php MD5 | findstr /I /V "certutil hash"
for /f %%N in ('dir /b C:\multiotp\contrib\*.* 2^>nul ^| find /c /v ""') do echo contrib : %%N fichiers - attendu 37
findstr /C:"ha_class_patched" C:\multiotp\multiotp.server.php >nul 2>&1
if errorlevel 1 (echo [INFO] serveur officiel ou ancien - optionnel pour le bouton Sync de l'application) else (echo [OK] serveur v4.0.10)

:SYNC_NOW
echo.
echo === SYNCHRONISATION FORCEE - log affiche en direct ===
echo Resultat attendu pour les comptes disparus de l'AD :
echo   Info: account xxx deleted by the AD/LDAP synchronization (no more in the AD/LDAP)
echo.
cd /d C:\multiotp
multiotp.exe -debug -display-log -ldap-users-sync

echo.
echo === Comptes ahmed restants sur disque ===
set FOUND=0
for /f "delims=" %%U in ('dir /b C:\multiotp\users 2^>nul ^| findstr /I "ahmed"') do (
  echo   encore present : users\%%U
  set FOUND=1
)
for /f "delims=" %%U in ('dir /b C:\multiotp\users_advanced 2^>nul ^| findstr /I "ahmed"') do (
  echo   encore present : users_advanced\%%U
  set FOUND=1
)
if %FOUND%==0 echo [OK] aucun compte ahmed sur disque : suppression effective.
echo.
echo Si des comptes figurent encore ci-dessus : lancez DIAGNOSTIC_sync_suppressions.cmd
echo et envoyez le rapport C:\multiotp\diagnostic_sync.txt
echo.
echo ===== TERMINE =====
pause
