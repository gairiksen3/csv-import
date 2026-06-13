' Launches the queue worker batch file with NO visible console window.
' Window style 0 = hidden, second arg False = don't wait for it to finish.
CreateObject("Wscript.Shell").Run "cmd /c """ & _
  "D:\xampp\htdocs\csv_import\scripts\queue-worker.bat""", 0, False
