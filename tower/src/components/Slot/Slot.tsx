import styles from './Slot.module.sass'
import {useDrop} from "react-dnd";
import {ConnectDropTarget} from "react-dnd/src/types";
import {ItemTypes} from "@/constants/draggable";

export type SlotProps = {
    id: string,
    position: string,
    onMutate: Function,
}

export default function Slot({id, position, onMutate}: SlotProps)
{
    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.MARKER,
        drop: ({ taskId, direction }: {taskId: string, direction: string}) => {
            const url = direction === "left" ?
                'http://localhost:8080/api/v1/set-start-date' :
                'http://localhost:8080/api/v1/set-finish-date';
            onMutate(() => {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        "jiraId": taskId,
                        "date": position,
                    }),
                }).then(res => res.json());
            }, {taskId: taskId, direction: direction, date: position});
        },
        collect: monitor => ({
            isOver: monitor.isOver(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    return (
        <div
            ref={drop}
            className={styles.container}
            style={{
                gridRow: `line-${id}-start/line-${id}-end`,
                gridColumn: `line-${position}-start/line-${position}-end`,
                border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
            }}
        />
    )
}
