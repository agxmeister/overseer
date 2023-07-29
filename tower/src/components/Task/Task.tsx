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
    const getDragSpec = (direction: string) => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id, direction: direction};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({
            isDraggingLeft: monitor.isDragging(),
        }),
    });

    const [{ isDraggingLeft }, dragLeft] = useDrag(() => getDragSpec("left"));
    const [{ isDraggingRight }, dragRight] = useDrag(() => getDragSpec("right"));

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
