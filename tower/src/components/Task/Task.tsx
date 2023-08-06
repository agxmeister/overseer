import styles from './Task.module.sass'
import {ReactElement} from "react";
import {useDrop} from "react-dnd";
import {CardProps} from "@/components/Card/Card";
import {ItemTypes} from "@/constants/draggable";
import {ConnectDropTarget} from "react-dnd/src/types";
import {MarkerPosition, MarkerProps} from "@/components/Marker/Marker";

export type TaskProps = {
    id: string,
    markerLeft: ReactElement<MarkerProps>
    markerRight: ReactElement<MarkerProps>
    start: string,
    finish: string,
    card: ReactElement<CardProps>,
    onLink: Function,
}

type Link = {
    key: string,
    type: string,
}

export default function Task({id, markerLeft, markerRight, start, finish, card, onLink}: TaskProps)
{
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
                        inwardJiraId: direction === MarkerPosition.Left ? id : taskId,
                        outwardJiraId: direction === MarkerPosition.Left ? taskId : id,
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

    return (
        <div ref={drop} className={styles.task} style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
            border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
        }}>
            {markerLeft}
            {card}
            {markerRight}
        </div>
    )
}
