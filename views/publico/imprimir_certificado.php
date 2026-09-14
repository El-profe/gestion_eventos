<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado - <?= htmlspecialchars($certificado['codigo_unico']) ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .diploma-container {
            width: 297mm;
            height: 209mm;
            margin: 10mm auto;
            background: #ffffff;
            padding: 15mm;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }
        .diploma-border {
            border: 4px double #1e293b;
            height: 100%;
            padding: 10mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
        }
        .titulo-inst { font-size: 26px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 2px; }
        .subtitulo-inst { font-size: 14px; color: #64748b; margin-bottom: 15px; }
        .titular-nombre { font-size: 32px; font-weight: 900; color: #1d4ed8; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; display: inline-block; padding-bottom: 5px; margin: 15px 0; }
        .evento-nombre { font-size: 20px; font-weight: 700; color: #0f172a; margin: 10px 0; }
        .firmas-grid { display: flex; justify-content: space-around; align-items: flex-end; margin-top: 25px; }
        .firma-box { width: 200px; text-align: center; border-top: 1px solid #64748b; padding-top: 5px; font-size: 13px; font-weight: 600; }
        .qr-box { position: absolute; bottom: 20mm; right: 20mm; text-align: center; font-size: 10px; color: #475569; }
        .anulado-watermark {
            position: absolute; top: 40%; left: 15%; transform: rotate(-30deg);
            font-size: 90px; color: rgba(239, 68, 68, 0.3); font-weight: 900; border: 8px solid rgba(239, 68, 68, 0.3);
            padding: 10px 40px; text-transform: uppercase; pointer-events: none;
        }
        @media print {
            body { background: none; }
            .diploma-container { margin: 0; box-shadow: none; width: 100vw; height: 100vh; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="text-center py-3 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 fw-bold">Imprimir / Descargar PDF</button>
        <a href="index.php?action=mis_certificados" class="btn btn-secondary px-3 ms-2">Volver</a>
    </div>

    <div class="diploma-container">
        <?php if ($certificado['estado'] === 'ANULADO'): ?>
            <div class="anulado-watermark">ANULADO</div>
        <?php endif; ?>

        <div class="diploma-border">
            <div>
                <div class="titulo-inst"><?= htmlspecialchars($config['nombre_institucion'] ?? 'UNIVERSIDAD DE FORMACIÓN CONTINUA') ?></div>
                <div class="subtitulo-inst"><?= htmlspecialchars($config['direccion_institucional'] ?? '') ?></div>
                <div style="font-size: 16px; font-style: italic; color: #475569; margin-top: 10px;">Otorga el presente</div>
                <h1 style="font-size: 42px; font-weight: 900; letter-spacing: 4px; color: #0f172a; margin: 5px 0;">CERTIFICADO</h1>
                <div style="font-size: 14px; color: #475569;">A:</div>
                <div class="titular-nombre"><?= htmlspecialchars($certificado['nombres'] . ' ' . $certificado['apellidos']) ?></div>
                <div style="font-size: 13px; color: #475569;">Con Cédula de Identidad N.° <strong><?= htmlspecialchars($certificado['ci']) ?></strong></div>
            </div>

            <div>
                <p style="font-size: 14px; color: #334155; margin: 0;">Por su destacada participación en calidad de <strong><?= htmlspecialchars($certificado['tipo_participacion']) ?></strong> en el:</p>
                <div class="evento-nombre">"<?= htmlspecialchars($certificado['evento_titulo']) ?>"</div>
                <p style="font-size: 12px; color: #64748b; margin: 0;">
                    Realizado del <?= date('d/m/Y', strtotime($certificado['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($certificado['fecha_fin'])) ?> con una carga horaria de <strong><?= $certificado['horas_academicas'] ?> horas académicas</strong>.
                </p>
            </div>

            <div class="firmas-grid">
                <div class="firma-box"><?= htmlspecialchars($config['cargo_firmante_1'] ?? 'Rector / Director') ?></div>
                <div class="firma-box"><?= htmlspecialchars($config['cargo_firmante_2'] ?? 'Coordinador Académico') ?></div>
            </div>
        </div>

        <div class="qr-box">
            <img src="<?= $qrImagen ?>" alt="Código QR" width="90" height="90"><br>
            <span>Folio: <?= htmlspecialchars($certificado['codigo_unico']) ?></span><br>
            <span style="font-size: 8px;">Escanea para validar</span>
        </div>
    </div>

</body>
</html>