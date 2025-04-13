import { spawn } from "child_process";

let serverProcess;

function startServer() {
  if (serverProcess) serverProcess.kill();

  console.log("🚀 Iniciando servidor ReactPHP...");
  serverProcess = spawn("php", ["public/index.php"], { stdio: "inherit" });
}

// Inicialmente iniciar servidor
startServer();

// Manejar cambios
process.on("SIGINT", () => {
  if (serverProcess) serverProcess.kill();
  process.exit();
});
