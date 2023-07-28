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
    const [{ isDragging }, drag] = useDrag(() => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({
            isDragging: monitor.isDragging(),
        }),
    }));

    return (
        <div className={styles.task} style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
        }}>
            <div ref={drag} className={styles.marker} style={{
                opacity: isDragging ? 0 : 1,
            }}/>
            {card}
        </div>
    )
}
