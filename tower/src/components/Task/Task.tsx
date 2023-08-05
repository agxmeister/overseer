import styles from './Task.module.sass'
import {ReactElement, useRef} from "react";
import {useDrag, useDrop} from "react-dnd";
import {CardProps} from "@/components/Card/Card";
import {ItemTypes} from "@/constants/draggable";
import {ConnectDropTarget} from "react-dnd/src/types";
import {Property} from "csstype";
import {MarkerProps} from "@/components/Marker/Marker";

export type TaskProps = {
    id: string,
    markerLeft: ReactElement<MarkerProps>
    markerRight: ReactElement<MarkerProps>
    start: string,
    finish: string,
    links: {inward: Link[], outward: Link[]},
    card: ReactElement<CardProps>,
    onScale: Function,
    onLink: Function,
    addMarker: Function,
}

type Link = {
    key: string,
    type: string,
}

export enum ScaleDirection {
    Left = "left",
    Right = "right",
}

export default function Task({id, markerLeft, markerRight, start, finish, links, card, onScale, onLink, addMarker}: TaskProps)
{
    const [{ isDraggingLeft }, dragLeft] = useDrag(() => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id, direction: ScaleDirection.Left};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({isDraggingLeft: monitor.isDragging()}),
    }));

    const [{ isDraggingRight }, dragRight] = useDrag(() => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id, direction: ScaleDirection.Right};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({isDraggingRight: monitor.isDragging()}),
    }));

    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.MARKER,
        drop: ({ taskId, direction }: {taskId: string, direction: string}) => {
            onLink(() => {
                return fetch(`http://localhost:8080/api/v1/links`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        inwardJiraId: direction === ScaleDirection.Left ? id : taskId,
                        outwardJiraId: direction === ScaleDirection.Left ? taskId : id,
                        type: 'Precedes',
                    }),
                });
            });
        },
        canDrop: ({ taskId }) => taskId !== id,
        collect: monitor => ({
            isOver: monitor.isOver() && monitor.canDrop(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    const leftMarkerRef = useRef<HTMLDivElement|null>(null);
    addMarker(id, 'left', leftMarkerRef);
    const rightMarkerRef = useRef<HTMLDivElement|null>(null);
    addMarker(id, 'right', rightMarkerRef);

    return (
        <div ref={drop} className={styles.task} style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
            border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
        }}>
            {links.inward.length > 0 ? (
                <div ref={leftMarkerRef} className={styles.linkLeft} style={{
                    gridColumn: "line-left-link",
                }}/>
            ) : null}
            {markerLeft}
            <div ref={dragLeft} className={styles.marker} style={{
                gridColumn: "line-left-marker",
                opacity: isDraggingLeft ? 0 : 1,
            }}/>
            {card}
            <div ref={dragRight} className={styles.marker} style={{
                gridColumn: "line-right-marker",
                opacity: isDraggingRight ? 0 : 1,
            }}/>
            {markerRight}
            {links.outward.length > 0 ? (
                <div ref={rightMarkerRef} className={styles.linkRight} style={{
                    gridColumn: "line-right-link"
                }}/>
            ) : null}
        </div>
    )
}
