!macro customInstall
  DetailPrint "Configuring Windows Firewall rules..."
  nsExec::ExecToLog '"$SYSDIR\\cmd.exe" /c netsh advfirewall firewall delete rule program="$INSTDIR\\${APP_EXECUTABLE_FILENAME}"'
  nsExec::ExecToLog '"$SYSDIR\\cmd.exe" /c netsh advfirewall firewall add rule name="${PRODUCT_NAME}" dir=in action=allow program="$INSTDIR\\${APP_EXECUTABLE_FILENAME}" enable=yes profile=any'
  nsExec::ExecToLog '"$SYSDIR\\cmd.exe" /c netsh advfirewall firewall add rule name="${PRODUCT_NAME} (out)" dir=out action=allow program="$INSTDIR\\${APP_EXECUTABLE_FILENAME}" enable=yes profile=any'
!macroend

!macro customUnInstall
  DetailPrint "Removing Windows Firewall rules..."
  nsExec::ExecToLog '"$SYSDIR\\cmd.exe" /c netsh advfirewall firewall delete rule program="$INSTDIR\\${APP_EXECUTABLE_FILENAME}"'
!macroend
