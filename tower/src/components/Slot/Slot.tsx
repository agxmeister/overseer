import styles from './Slot.module.sass'
import {useDrop} from "react-dnd";
import {ItemTypes} from "@/constants/draggable";
import {ConnectDropTarget} from "react-dnd/src/types";

export type SlotProps = {
    id: string,
    position: string,
    onMutate: Function,
}

export default function Slot({id, position, onMutate}: SlotProps)
{
    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.CARD,
        drop: ({ cardId }: {cardId: string}) => {
            onMutate(() => {
                return fetch('http://localhost:8080/api/v1/set-start-date', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        "jiraId": cardId,
                        "startDate": position,
                    }),
                }).then(res => res.json());
            }, {cardId: cardId, startDate: position});
        },
        collect: monitor => ({
            isOver: monitor.isOver(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    return <div
        ref={drop}
        className={styles.container}
        style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${position}-start/line-${position}-end`,
            border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
        }}
    />
}
