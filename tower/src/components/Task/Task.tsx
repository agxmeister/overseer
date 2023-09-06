import styles from './Task.module.sass'
import {ReactElement, useContext} from "react";
import {useDrop} from "react-dnd";
import {CardProps} from "@/components/Card/Card";
import {ItemType} from "@/constants/draggable";
import {ConnectDropTarget} from "react-dnd/src/types";
import {MarkerPosition, MarkerProps} from "@/components/Marker/Marker";
import {MapContext} from "@/components/Map/Map";

export type TaskProps = {
    id: string,
    markerLeft: ReactElement<MarkerProps>
    markerRight: ReactElement<MarkerProps>
    begin: string,
    end: string,
    card: ReactElement<CardProps>,
    onLink: Function,
}

type Link = {
    key: string,
    type: string,
}

export default function Task({id, markerLeft, markerRight, begin, end, card, onLink}: TaskProps)
{
    const {dates} = useContext(MapContext);

    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemType.MARKER,
        drop: ({ taskId, direction }: {taskId: string, direction: string}) => {
            onLink(
                direction === MarkerPosition.Left ? id : taskId,
                direction === MarkerPosition.Left ? taskId : id,
            );
        },
        canDrop: ({ taskId }) => taskId !== id,
        collect: monitor => ({
            isOver: monitor.isOver() && monitor.canDrop(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    const minDate = dates[0];
    const maxDate = dates[dates.length - 1];
    const startLineName = begin >= minDate ? begin : minDate;
    const endLineName = end <= maxDate ? end : maxDate;

    return end >= minDate && begin <= maxDate ? (
        <div ref={drop} className={styles.task} style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${startLineName}-start/line-${endLineName}-end`,
            border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
        }}>
            {begin >= minDate ? markerLeft : null}
            {card}
            {end <= maxDate ? markerRight : null}
        </div>
    ) : null;
}
