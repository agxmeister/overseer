import {useEffect, useRef, useState} from "react";

type LinkProps = {
    startMarkerId: string,
    finishMarkerId: string,
}

type Coords = {
    fromX: number,
    fromY: number,
    toX: number,
    toY: number,
}

export default function Link({ startMarkerId, finishMarkerId }: LinkProps)
{
    const [coords, setCoords] = useState<Coords|null>(null);

    const boxRef = useRef<HTMLDivElement|null>(null);

    useEffect(() => {
        const boxX = boxRef.current?.parentElement?.offsetLeft ?? 0;
        const boxY = boxRef.current?.parentElement?.offsetTop ?? 0;
        const startRef = document.getElementById(`marker-${startMarkerId}-right`);
        const startX = startRef?.offsetLeft ?? 0;
        const startY = startRef?.offsetTop ?? 0;
        const finishRef = document.getElementById(`marker-${finishMarkerId}-left`);
        const finishX = finishRef?.offsetLeft ?? 0;
        const finishY = finishRef?.offsetTop ?? 0;
        setCoords({
            fromX: startX - boxX,
            fromY: startY - boxY,
            toX: finishX - boxX,
            toY: finishY - boxY,
        });
        console.log(`Link from ${startMarkerId} (${startX},${startY}) to ${finishMarkerId} (${finishX},${finishY})`);
    }, []);

    if (!coords) {
        return <div ref={boxRef}/>
    }

    const canvasStartPoint = {
        x: Math.min(coords.fromX, coords.toX),
        y: Math.min(coords.fromY, coords.toY),
    };
    const canvasWidth = Math.abs(coords.toX - coords.fromX);
    const canvasHeight = Math.abs(coords.toY - coords.fromY);

    return (
        <svg
            width={canvasWidth}
            height={canvasHeight}
            style={{
                backgroundColor: "rgba(0, 0, 0, 0)",
                transform: `translate(${canvasStartPoint.x}px, ${canvasStartPoint.y}px)`,
            }}
        >
            <line
                stroke={"rgb(0, 0, 0)"}
                strokeWidth={2}
                x1={coords.fromX - canvasStartPoint.x}
                y1={coords.fromY - canvasStartPoint.y}
                x2={coords.toX - canvasStartPoint.x}
                y2={coords.toY - canvasStartPoint.y}
            />
        </svg>
    );
}
