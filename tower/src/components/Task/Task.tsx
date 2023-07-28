import styles from './Task.module.sass'
import {ReactElement} from "react";
import {useDrag} from "react-dnd";
import {CardProps} from "@/components/Card/Card";
import {ItemTypes} from "@/constants/draggable";

export type TaskProps = {
    id: string,
    start: string,
    finish: string,
    card: ReactElement<CardProps>,
    onScale: Function,
}
export default function Task({id, start, finish, card, onScale}: TaskProps)
{
    const [{ isDraggingLeft }, dragLeft] = useDrag(() => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id, direction: "left"};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({
            isDraggingLeft: monitor.isDragging(),
        }),
    }));

    const [{ isDraggingRight }, dragRight] = useDrag(() => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id, direction: "right"};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({
            isDraggingRight: monitor.isDragging(),
        }),
    }));

    return (
        <div className={styles.task} style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
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
