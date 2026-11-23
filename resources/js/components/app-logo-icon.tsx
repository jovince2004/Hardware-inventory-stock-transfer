import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="https://cdn-icons-png.flaticon.com/128/3061/3061160.png"
            alt={props.alt ?? 'Logo'}
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}
