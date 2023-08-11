import styles from './Link.module.sass'
import {useContext, useEffect, useRef, useState} from "react";
import {MapContext} from "@/components/Map/Map";

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
    const [scale, tasks] = useContext(MapContext);

    const [coords, setCoords] = useState<Coords|null>(null);

    const boxRef = useRef<HTMLDivElement|null>(null);

    useEffect(() => {
        const startRef = document.getElementById(`marker-${startMarkerId}-right`);
        const finishRef = document.getElementById(`marker-${finishMarkerId}-left`);
        const parentRef = boxRef.current?.parentElement;
        if (!startRef || !finishRef || !parentRef) {
            return;
        }

        const startX = startRef.offsetLeft + startRef.offsetWidth;
        const startY = startRef.offsetTop + startRef.offsetHeight / 2;

        const finishX = finishRef.offsetLeft;
        const finishY = finishRef.offsetTop + finishRef.offsetHeight / 2;

        const offsetX = parentRef.offsetLeft;
        const offsetY = parentRef.offsetTop;

        setCoords({
            fromX: startX - offsetX,
            fromY: startY - offsetY,
            toX: finishX - offsetX,
            toY: finishY - offsetY,
        });
    }, [scale, tasks]);

    if (!coords) {
        return <div ref={boxRef}/>
    }

    const canvasStartPoint = {
        x: Math.min(coords.fromX, coords.toX) - 34,
        y: Math.min(coords.fromY, coords.toY) - 34,
    };
    const canvasWidth = Math.abs(coords.toX - coords.fromX) + 68;
    const canvasHeight = Math.abs(coords.toY - coords.fromY) + 68;

    const points = ((coords.fromX + 64) < coords.toX ? [{
            x: coords.fromX + 2 - canvasStartPoint.x,
            y: coords.fromY - canvasStartPoint.y,
        }, {
            x: coords.fromX - canvasStartPoint.x + (coords.toX - coords.fromX) / 2,
            y: coords.fromY - canvasStartPoint.y,
        }, {
            x: coords.fromX - canvasStartPoint.x + (coords.toX - coords.fromX) / 2,
            y: coords.toY - canvasStartPoint.y,
        }, {
            x: coords.toX - 4 - canvasStartPoint.x,
            y: coords.toY - canvasStartPoint.y,
        }] : [{
            x: coords.fromX + 2 - canvasStartPoint.x,
            y: coords.fromY - canvasStartPoint.y,
        }, {
            x: coords.fromX + 32 - canvasStartPoint.x,
            y: coords.fromY - canvasStartPoint.y,
        }, {
            x: coords.fromX + 32 - canvasStartPoint.x,
            y: coords.fromY - canvasStartPoint.y + (coords.toY - coords.fromY) / 2,
        }, {
            x: coords.toX - 32 - canvasStartPoint.x,
            y: coords.fromY - canvasStartPoint.y + (coords.toY - coords.fromY) / 2,
        }, {
            x: coords.toX - 32 - canvasStartPoint.x,
            y: coords.toY - canvasStartPoint.y,
        }, {
            x: coords.toX - 4 - canvasStartPoint.x,
            y: coords.toY - canvasStartPoint.y,
        }]).reduce((acc, point) => `${acc}${acc ? ' ' : ''}${point.x},${point.y}`, "");

    return (
        <div className={styles.link} ref={boxRef}>
            <svg
                width={canvasWidth}
                height={canvasHeight}
                style={{
                    backgroundColor: "rgba(0, 0, 0, 0)",
                    transform: `translate(${canvasStartPoint.x}px, ${canvasStartPoint.y}px)`,
                }}
            >
                <defs>
                    <marker
                        id='head'
                        viewBox="-10 -10 20 20"
                        orient="auto"
                        markerWidth="20"
                        markerHeight="20"
                    >
                        <polygon points="-7,-3 1,0 -7,3" />
                    </marker>
                </defs>
                <polyline
                    stroke={"rgb(0, 0, 0)"}
                    strokeWidth={2}
                    fill={"none"}
                    points={points}
                    markerEnd={"url(#head)"}
                />
            </svg>
        </div>
    );
}
