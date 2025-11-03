export async function applyWatermark(imageUrl: string, watermarkUrl: string): Promise<string> {
    const [image, watermark] = await Promise.all([loadImage(imageUrl), loadImage(watermarkUrl)]);

    const scaleFactor = 1;
    const canvas = document.createElement('canvas');

    canvas.width = image.width * scaleFactor;
    canvas.height = image.height * scaleFactor;

    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas not supported');

    // Draw base image
    ctx.drawImage(image, 0, 0);

    // Set watermark transparency
    ctx.globalAlpha = 0.15;

    // Draw watermark scaled to full image
    //ctx.drawImage(watermark, 0, 0, image.width, image.height);

    // Configure watermark size and spacing
    const wsize = image.width / 16;
    const watermarkWidth = wsize;
    const watermarkHeight = wsize;
    const spacingX = wsize;
    const spacingY = wsize;

    // Calculate how many watermarks fit across and down
    const cols = Math.ceil(canvas.width / spacingX) + 1;
    const rows = Math.ceil(canvas.height / spacingY) + 1;

    // Draw watermarks in a grid pattern
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const x = col * spacingX - watermarkWidth / 2;
            const y = row * spacingY - watermarkHeight / 2;

            // Only draw if the watermark would be visible
            if (x + watermarkWidth > 0 && y + watermarkHeight > 0 && x < canvas.width && y < canvas.height) {
                ctx.drawImage(watermark, x, y, watermarkWidth, watermarkHeight);
            }
        }
    }

    // Reset alpha
    ctx.globalAlpha = 1;

    return canvas.toDataURL('image/jpeg', 0.2);
}

function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}
