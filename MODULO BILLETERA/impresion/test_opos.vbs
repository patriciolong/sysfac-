Set opos = CreateObject("OPOS.POSPrinter")
WScript.Echo "OPOS created"
opos.Open "LR2000"
opos.ClaimDevice 1000
opos.DeviceEnabled = True
WScript.Echo "OPOS opened LR2000"
opos.PrintNormal 2, "Test from OPOS" & vbCrLf
opos.DeviceEnabled = False
opos.ReleaseDevice
opos.Close
WScript.Echo "Done"
