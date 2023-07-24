'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import Trace from "@/components/Trace/Trace";
import Task from "@/components/Task/Task";
import {useState} from "react";
import Slot from "@/components/Slot/Slot";
import {getDates} from "@/utils/date";

type Issue = {
    key: string,
    estimatedStartDate: string,
    estimatedFinishDate: string,
    summary: string,
}

export default function Page() {
    const [moveCardId, setMoveCardId] = useState<string|null>(null);
    const onCardMove = (cardId: string) => {
        setMoveCardId(cardId);
    }

    const dates = getDates(new Date("2023-07-20"), new Date("2023-07-30"));

    const {data} = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    const tasks = data ? data.map((issue: Issue) =>
        <Task key={issue.key} id={issue.key} trace={
            <Trace
                id={issue.key}
                start={issue.estimatedStartDate}
                finish={issue.estimatedFinishDate}
            />
        } card={
            <Card
                key={issue.key}
                id={issue.key}
                start={issue.estimatedStartDate}
                finish={issue.estimatedFinishDate}
                title={issue.summary}
                onMove={onCardMove}
            />
        } slots={issue.key === moveCardId ? dates.filter(date => date < issue.estimatedStartDate || date > issue.estimatedFinishDate).map(date => <Slot key={date} id={issue.key} position={date}/>) : []}/>
    ): [];
    return <Map dates={dates} tasks={tasks}/>
}
