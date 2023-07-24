import styles from './Card.module.sass'
import {format} from "@/utils/date";
import {useDrag} from "react-dnd";
import {ItemTypes} from "@/constants/draggable";

export type CardProps = {
    id: string,
    start: string,
    finish: string,
    title: string,
}

export default function Card({ id, start, finish, title }: CardProps)
{
    const [{isDragging}, drag] = useDrag(() => ({
        type: ItemTypes.CARD,
        item: {cardId: id},
        collect: monitor => ({
            isDragging: monitor.isDragging(),
        }),
    }));

    const now = format(new Date());
    const date = now > start ? now < finish ? now : finish ?? start : start;
    return (
        <div
            ref={drag}
            role={"heading"}
            className={styles.container}
            style={{
                gridRow: `line-${id}-start/line-${id}-end`,
                gridColumn: `line-${date}-start/line-${date}-end`,
                opacity: isDragging ? 0.5 : 1,
                cursor: 'move',
            }}
        >
            <div className={styles.title}>{title}</div>
        </div>
    )
}
