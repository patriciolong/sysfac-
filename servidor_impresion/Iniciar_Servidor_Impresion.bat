@echo off
title SysFact+ Servidor de Impresion Termica
echo ===================================================
echo     SYSFACT+ - SERVIDOR DE IMPRESION TERMICA
echo ===================================================
echo Iniciando servicio de impresion local en puerto 8080...
if exist "dist\SysFact_Printer.exe" (
    start "" "dist\SysFact_Printer.exe"
) else if exist "SysFact_Printer.exe" (
    start "" "SysFact_Printer.exe"
) else (
    py -3 servidor_impresion.py
)
exit
