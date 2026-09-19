On Error Resume Next
Set opos = CreateObject("OPOS.POSPrinter")
If Err.Number <> 0 Then
    WScript.Echo "Error creating OPOS: " & Err.Description
    WScript.Quit 1
End If

opos.Open "LR2000"
If opos.ResultCode <> 0 Then
    WScript.Echo "Error opening LR2000. ResultCode: " & opos.ResultCode
    WScript.Quit 1
End If

opos.ClaimDevice 1000
If opos.ResultCode <> 0 Then
    WScript.Echo "Error claiming device. ResultCode: " & opos.ResultCode
    WScript.Quit 1
End If

opos.DeviceEnabled = True
If opos.ResultCode <> 0 Then
    WScript.Echo "Error enabling device. ResultCode: " & opos.ResultCode
    WScript.Quit 1
End If

opos.PrintNormal 2, "Test from OPOS" & vbCrLf & vbCrLf & vbCrLf & chr(27) & "|100fP" ' ESC|100fP is OPOS cut
If opos.ResultCode <> 0 Then
    WScript.Echo "Error printing. ResultCode: " & opos.ResultCode
End If

opos.DeviceEnabled = False
opos.ReleaseDevice
opos.Close
WScript.Echo "Done"
