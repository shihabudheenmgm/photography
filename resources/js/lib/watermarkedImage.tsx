import { applyWatermark } from '@/lib/applyWatermark';
import React, { useEffect, useState } from 'react';

const watermarkUrl = '/images/watermark.png';

type Props = {
    src: string;
    idx: number;
    classname: string;
};

const WatermarkedImage: React.FC<Props> = ({ src, idx, classname }) => {
    const [watermarkedSrc, setWatermarkedSrc] = useState<string>('');

    useEffect(() => {
        applyWatermark(src, watermarkUrl).then(setWatermarkedSrc);
    }, [src]);

    if (!watermarkedSrc) return <div className="aspect-square w-full animate-pulse bg-gray-200" />;

    return <img src={watermarkedSrc} alt={`Image ${idx + 1}`} className={`block cursor-pointer shadow ${classname}`} />;
};

export default WatermarkedImage;
