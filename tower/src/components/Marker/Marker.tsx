import styles from "@/components/MArker/Marker.module.sass";
import {useRef} from "react";

export enum MarkerPosition {
    Left = "left",
    Right = "right",
}

export type MarkerProps = {
    position: MarkerPosition
}

export default function Marker({ position }: MarkerProps)
{
    const ref = useRef<HTMLDivElement|null>(null);
    return (
        <div ref={ref} className={styles.marker} style={{
            gridColumn: `line-${position}-marker`,
        }}/>
    );
}
