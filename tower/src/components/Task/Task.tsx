import {ReactElement} from "react";
import {TraceProps} from "@/components/Trace/Trace";
import {CardProps} from "@/components/Card/Card";
import {SlotProps} from "@/components/Slot/Slot";

export type TaskProps = {
    id: string,
    trace: ReactElement<TraceProps>,
    card: ReactElement<CardProps>,
    slots: ReactElement<SlotProps>[],
}
export default function Task({id, trace, card, slots = []}: TaskProps)
{
    return <>
        {trace}
        {card}
        {slots}
    </>
}
