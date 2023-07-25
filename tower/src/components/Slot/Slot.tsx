import styles from './Slot.module.sass'
import {useDrop} from "react-dnd";
import {ItemTypes} from "@/constants/draggable";
import {ConnectDropTarget} from "react-dnd/src/types";
import useSWRMutation from "swr/mutation";

export type SlotProps = {
    id: string,
    position: string,
}

async function setStartDate(url: string, { arg }: { arg: { jiraId: string, startDate: string }}) {
    await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            "jiraId": arg.jiraId,
            "startDate": arg.startDate,
        }),
    })
}

export default function Slot({id, position}: SlotProps)
{
    const { trigger } = useSWRMutation('http://localhost:8080/api/v1/set-start-date', setStartDate);

    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.CARD,
        drop: ({ cardId }: {cardId: string}) => trigger({
            jiraId: cardId,
            startDate: position,
        }),
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
