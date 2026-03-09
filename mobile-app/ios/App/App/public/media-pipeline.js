function formatBytes(bytes) {
  if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const base = Math.floor(Math.log(bytes) / Math.log(1024));
  const unit = units[Math.min(base, units.length - 1)];
  const value = bytes / 1024 ** base;
  return `${value.toFixed(value >= 10 ? 0 : 1)} ${unit}`;
}

function loadImage(file) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve(img);
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('Falha ao ler imagem.'));
    };
    img.src = url;
  });
}

function canvasToBlob(canvas, type, quality) {
  return new Promise((resolve) => {
    canvas.toBlob((blob) => resolve(blob), type, quality);
  });
}

export async function optimizeImage(file, options = {}) {
  const maxWidth = options.maxWidth ?? 1600;
  const maxHeight = options.maxHeight ?? 1600;
  const quality = options.quality ?? 0.82;
  const outputType = options.outputType ?? 'image/jpeg';

  const image = await loadImage(file);
  const ratio = Math.min(maxWidth / image.width, maxHeight / image.height, 1);
  const width = Math.max(1, Math.round(image.width * ratio));
  const height = Math.max(1, Math.round(image.height * ratio));

  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = height;
  const ctx = canvas.getContext('2d');
  if (!ctx) {
    throw new Error('Nao foi possivel otimizar imagem.');
  }
  ctx.drawImage(image, 0, 0, width, height);

  const optimizedBlob = await canvasToBlob(canvas, outputType, quality);
  if (!optimizedBlob) {
    throw new Error('Falha ao gerar blob otimizado.');
  }

  const optimizedFile = new File(
    [optimizedBlob],
    file.name.replace(/\.[a-z0-9]+$/i, '.jpg'),
    { type: outputType }
  );

  return {
    kind: 'image',
    original: file,
    optimized: optimizedFile,
    originalSize: file.size,
    optimizedSize: optimizedFile.size,
    compressionRatio: file.size > 0 ? 1 - optimizedFile.size / file.size : 0,
    report: `${file.name}: ${formatBytes(file.size)} -> ${formatBytes(optimizedFile.size)}`,
  };
}

export async function inspectVideo(file) {
  const mimeType = file.type || 'video/unknown';
  const size = file.size;
  let recommendation = 'upload_direct';

  if (size > 120 * 1024 * 1024) {
    recommendation = 'compress_heavy';
  } else if (size > 45 * 1024 * 1024) {
    recommendation = 'compress_medium';
  }

  return {
    kind: 'video',
    original: file,
    originalSize: size,
    optimizedSize: size,
    compressionRatio: 0,
    recommendation,
    report: `${file.name}: ${formatBytes(size)} (${mimeType})`,
  };
}

export function humanCompression(ratio) {
  if (!Number.isFinite(ratio) || ratio <= 0) return '0%';
  return `${(ratio * 100).toFixed(1)}%`;
}

