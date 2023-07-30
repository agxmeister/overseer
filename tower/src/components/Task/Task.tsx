import styles from './Task.module.sass'
import {ReactElement} from "react";
import {useDrag, useDrop} from "react-dnd";
import {CardProps} from "@/components/Card/Card";
import {ItemTypes} from "@/constants/draggable";
import {ConnectDropTarget} from "react-dnd/src/types";

export type TaskProps = {
    id: string,
    start: string,
    finish: string,
    card: ReactElement<CardProps>,
    onScale: Function,
    onLink: Function,
}

export enum ScaleDirection {
    Left = "left",
    Right = "right",
}

export default function Task({id, start, finish, card, onScale, onLink}: TaskProps)
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
            isOver: monitor.isOver(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    return (
        <div ref={drop} className={styles.task} style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
            border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
        }}>
            <div ref={dragLeft} className={styles.marker} style={{
                gridColumn: "1/1",
                opacity: isDraggingLeft ? 0 : 1,
            }}/>
            {card}
            <div ref={dragRight} className={styles.marker} style={{
                gridColumn: "3/3",
                opacity: isDraggingRight ? 0 : 1,
            }}/>
        </div>
    )
}
