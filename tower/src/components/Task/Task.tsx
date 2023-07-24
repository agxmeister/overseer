import {ReactElement} from "react";
import {TraceProps} from "@/components/Trace/Trace";
import {CardProps} from "@/components/Card/Card";

export type TaskProps = {
    id: string,
    trace: ReactElement<TraceProps>,
    card: ReactElement<CardProps>,
}
export default function Task({id, trace, card}: TaskProps)
{
    return <>
        {trace}
        {card}
    </>
}
