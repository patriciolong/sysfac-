import tkinter as tk
from tkinter import ttk, messagebox
import win32print
import pystray
from PIL import Image, ImageDraw
import win32ui
import win32con
import json
import os
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer
import sys

if getattr(sys, 'frozen', False):
    application_path = os.path.dirname(sys.executable)
else:
    application_path = os.path.dirname(os.path.abspath(__file__))

CONFIG_FILE = os.path.join(application_path, "printer_config.json")

class PrintServerApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Servidor de Impresión")
        self.root.geometry("400x260")
        self.root.resizable(False, False)
        self.root.configure(padx=20, pady=15)

        self.config = self.load_config()
        self.httpd = None
        
        self.create_widgets()
        
        self.root.protocol('WM_DELETE_WINDOW', self.hide_window)
        
        self.server_thread = threading.Thread(target=self.start_server, daemon=True)
        self.server_thread.start()

    def load_config(self):
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, 'r') as f:
                    return json.load(f)
            except Exception:
                pass
        return {"printer": "", "print_mode": "RAW/ESC-POS (Spooler Windows)"}

    def save_config(self):
        self.config["printer"] = self.printer_var.get()
        self.config["print_mode"] = self.print_mode_var.get()
        try:
            with open(CONFIG_FILE, 'w') as f:
                json.dump(self.config, f, indent=4)
            messagebox.showinfo("Guardado", "Configuración de impresora guardada con éxito.\nYa puedes cerrar esta ventana para que siga ejecutándose en segundo plano.")
        except Exception as e:
            messagebox.showerror("Error", f"No se pudo guardar la configuración:\n{e}")

    def create_widgets(self):
        lbl_status = tk.Label(self.root, text="🟢 Escuchando puerto 8080...", fg="green", font=("Arial", 11, "bold"))
        lbl_status.pack(pady=(0, 15))

        frame = ttk.Frame(self.root)
        frame.pack(fill=tk.X)

        ttk.Label(frame, text="1. Impresora Destino:", font=("Arial", 10)).grid(row=0, column=0, sticky=tk.W, pady=5)
        
        printers = []
        try:
            flags = win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
            printers = [p[2] for p in win32print.EnumPrinters(flags)]
        except:
            pass

        self.printer_var = tk.StringVar()
        cb_printers = ttk.Combobox(frame, textvariable=self.printer_var, values=printers, state="readonly", width=40)
        cb_printers.grid(row=1, column=0, pady=(0, 15))
        
        if self.config.get("printer") in printers:
            cb_printers.set(self.config.get("printer"))
        elif printers:
            cb_printers.current(0)

        ttk.Label(frame, text="2. Modo de Impresión:", font=("Arial", 10)).grid(row=2, column=0, sticky=tk.W, pady=5)
        self.print_mode_var = tk.StringVar()
        modes = ["Estándar Windows (GDI)", "RAW/ESC-POS (Spooler Windows)", "OPOS Nativo (Solo LR2000)"]
        cb_mode = ttk.Combobox(frame, textvariable=self.print_mode_var, values=modes, state="readonly", width=40)
        cb_mode.grid(row=3, column=0, pady=(0, 15))
        
        if self.config.get("print_mode") in modes:
            cb_mode.set(self.config.get("print_mode"))
        else:
            cb_mode.current(1)

        btn_save = tk.Button(self.root, text="Guardar y Aplicar", command=self.save_config, bg="#2196F3", fg="white", font=("Arial", 10, "bold"), pady=5)
        btn_save.pack(fill=tk.X, pady=10)

    def hide_window(self):
        self.root.withdraw()
        image = self.create_image()
        menu = pystray.Menu(
            pystray.MenuItem("Mostrar Configuración", self.show_window, default=True),
            pystray.MenuItem("Salir del Servidor", self.quit_window)
        )
        self.icon = pystray.Icon("PrintServer", image, "Servidor de Impresión", menu)
        threading.Thread(target=self.icon.run, daemon=True).start()

    def show_window(self, icon, item):
        self.icon.stop()
        self.root.after(0, self.root.deiconify)

    def quit_window(self, icon, item):
        self.icon.stop()
        self.root.after(0, self.root.destroy)
        os._exit(0)

    def create_image(self):
        image = Image.new('RGB', (64, 64), color=(255, 255, 255))
        dc = ImageDraw.Draw(image)
        # Dibujando un ícono de impresora básico
        dc.rectangle((16, 32, 48, 56), fill=(100, 100, 100)) # Base
        dc.rectangle((24, 16, 40, 32), fill=(200, 200, 200)) # Papel entrada
        dc.rectangle((20, 40, 44, 60), fill=(240, 240, 240)) # Papel salida
        return image

    def start_server(self):
        server_address = ('127.0.0.1', 8080)
        outer_self = self
        
        class WebPrintHandler(BaseHTTPRequestHandler):
            def log_message(self, format, *args):
                pass
                
            def do_OPTIONS(self):
                self.send_response(200, "ok")
                self.send_header('Access-Control-Allow-Origin', '*')
                self.send_header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                self.send_header("Access-Control-Allow-Headers", "X-Requested-With, Content-type")
                self.end_headers()

            def do_POST(self):
                if self.path == '/print_receipt':
                    content_length = int(self.headers['Content-Length'])
                    post_data = self.rfile.read(content_length)
                    data = json.loads(post_data.decode('utf-8'))

                    try:
                        self.print_receipt(data)
                        self.send_response(200)
                        self.send_header('Access-Control-Allow-Origin', '*')
                        self.send_header('Content-type', 'application/json')
                        self.end_headers()
                        self.wfile.write(json.dumps({'success': True, 'message': 'Impreso.'}).encode('utf-8'))
                    except Exception as e:
                        self.send_response(500)
                        self.send_header('Access-Control-Allow-Origin', '*')
                        self.send_header('Content-type', 'application/json')
                        self.end_headers()
                        self.wfile.write(json.dumps({'success': False, 'message': str(e)}).encode('utf-8'))
                else:
                    self.send_response(404)
                    self.end_headers()

            def print_receipt(handler_self, data):
                printer_name = outer_self.config.get("printer")
                if not printer_name:
                    raise Exception("No hay impresora seleccionada en el programa servidor.")

                print_mode = outer_self.config.get("print_mode", "Estándar Windows (GDI)")

                if data.get("type") == "standard":
                    title = ""
                    discount_text = ""
                    promo_title = data.get("promo_title", "¡Tu siguiente par tiene!")
                    promo_discount = data.get("promo_discount", "50% OFF")
                    desc = data.get("description", "Presenta este cupón en tu\npróxima compra.")
                    valid = data.get("valid_until", "31 AGOSTO")
                    coupon_no = data.get("coupon_no", "______")
                    
                    details = (
                        f"           VERSSATO             \n"
                        f"      www.verssato.com.ec       \n"
                        f"   FB | IG | TK: @verssato.ec   \n"
                        f"================================\n"
                        f"\n"
                        f"    {promo_title.center(28)}    \n"
                        f"          {promo_discount.center(12)}          \n"
                        f"\n"
                        f"--------------------------------\n"
                    )
                    for line in desc.split('\n'):
                        details += f" {line.center(30)} \n"
                    
                    details += (
                        f"--------------------------------\n"
                        f"\n"
                        f"CUPÓN VÁLIDO HASTA {valid}\n"
                        f"\n"
                        f"* APLICA RESTRICCIONES.         \n"
                        f"Nro: {coupon_no}\n"
                        f"================================\n"
                    )
                elif "content" in data:
                    title = data.get("title", "")
                    discount_text = data.get("subtitle", "")
                    details = data.get("content", "")
                else:
                    title = "VERSSATO"
                    subtitle = "TICKET DE CONSUMO"
                    code = data.get("code", "N/A")
                    seller = data.get("seller", "N/A")
                    amount = data.get("amount", "0.00")
                    balance = data.get("balance", "0.00")
                    invoice = data.get("invoice", "N/A")
                    date = data.get("date", "N/A")
                    
                    details = (
                        f"================================\n"
                        f"Fecha: {date}\n"
                        f"Factura: {invoice}\n"
                        f"Vendedor: {seller}\n"
                        f"================================\n"
                        f"GIFT CARD: {code}\n"
                        f"--------------------------------\n"
                        f"CONSUMO:      ${amount}\n"
                        f"SALDO ACTUAL: ${balance}\n"
                        f"================================\n"
                        f"    ¡Gracias por su visita!     \n"
                    )
                    discount_text = subtitle

                if print_mode == "OPOS Nativo (Solo LR2000)":
                    outer_self.print_opos_native(printer_name, title, discount_text, details)
                elif print_mode == "RAW/ESC-POS (Spooler Windows)":
                    outer_self.print_raw_thermal(printer_name, title, discount_text, details)
                else:
                    outer_self.print_gdi_windows(printer_name, title, discount_text, details)

        try:
            self.httpd = HTTPServer(server_address, WebPrintHandler)
            self.httpd.serve_forever()
        except Exception as e:
            import tkinter.messagebox
            outer_self.root.after(0, lambda: tkinter.messagebox.showerror("Error de Red", f"El puerto 8080 está bloqueado.\nCierre la otra aplicación.\n\n{e}"))

    def print_opos_native(self, printer_name, title, discount, details):
        import subprocess, tempfile
        vbs_code = f"""On Error Resume Next
Set opos = CreateObject("OPOS.POSPrinter")
opos.Open "{printer_name}"
opos.ClaimDevice 1000
opos.DeviceEnabled = True
Dim ESC: ESC = Chr(27)
Dim txt
txt = ESC & "|cA" & ESC & "|bC" & ESC & "|2C" & "{title}" & vbCrLf
txt = txt & ESC & "|1C" & "{discount}" & vbCrLf & vbCrLf
txt = txt & ESC & "|N" & "{details.replace(chr(10), '" & vbCrLf & "')}" & vbCrLf & vbCrLf
txt = txt & ESC & "|100fP"
opos.PrintNormal 2, txt
opos.DeviceEnabled = False
opos.ReleaseDevice
opos.Close
WScript.Quit 0
"""
        vbs_file = os.path.join(tempfile.gettempdir(), "print_opos.vbs")
        with open(vbs_file, "w", encoding="ansi", errors="replace") as f: f.write(vbs_code)
        cscript = r"C:\Windows\SysWOW64\cscript.exe" if os.path.exists(r"C:\Windows\SysWOW64\cscript.exe") else "cscript.exe"
        subprocess.run([cscript, "//nologo", "//B", vbs_file])

    def print_raw_thermal(self, printer_name, title, discount, details):
        ESC = b'\x1b'
        GS = b'\x1d'
        raw_data = ESC + b'@' + ESC + b'a\x01' + ESC + b'E\x01' + GS + b'!\x11'
        raw_data += (title + '\n').encode('cp850', 'replace')
        raw_data += GS + b'!\x00' + (discount + '\n\n').encode('cp850', 'replace')
        raw_data += ESC + b'E\x00' + ESC + b'a\x00'
        raw_data += (details + '\n\n\n\n').encode('cp850', 'replace') + GS + b'V\x41\x00'
        
        hPrinter = win32print.OpenPrinter(printer_name)
        try:
            win32print.StartDocPrinter(hPrinter, 1, ("Recibo", None, "RAW"))
            win32print.StartPagePrinter(hPrinter)
            win32print.WritePrinter(hPrinter, raw_data)
            win32print.EndPagePrinter(hPrinter)
            win32print.EndDocPrinter(hPrinter)
        finally:
            win32print.ClosePrinter(hPrinter)

    def print_gdi_windows(self, printer_name, title, discount, details):
        hDC = win32ui.CreateDC()
        hDC.CreatePrinterDC(printer_name)
        dpi_y = hDC.GetDeviceCaps(win32con.LOGPIXELSY)
        hDC.StartDoc("Recibo")
        hDC.StartPage()

        font_title = win32ui.CreateFont({"name": "Arial", "height": int(dpi_y * 0.20), "weight": 800})
        font_discount = win32ui.CreateFont({"name": "Arial", "height": int(dpi_y * 0.15), "weight": 700})
        font_details = win32ui.CreateFont({"name": "Courier New", "height": int(dpi_y * 0.10)})

        margin_x = int(dpi_y * 0.1)
        y_pos = int(dpi_y * 0.1)

        hDC.SelectObject(font_title)
        hDC.TextOut(margin_x, y_pos, title)
        y_pos += int(dpi_y * 0.25)

        hDC.SelectObject(font_discount)
        hDC.TextOut(margin_x, y_pos, discount)
        y_pos += int(dpi_y * 0.35)

        hDC.SelectObject(font_details)
        for line in details.split('\n'):
            hDC.TextOut(margin_x, y_pos, line)
            y_pos += int(dpi_y * 0.15)

        hDC.EndPage()
        hDC.EndDoc()
        hDC.DeleteDC()

if __name__ == "__main__":
    root = tk.Tk()
    app = PrintServerApp(root)
    root.mainloop()
