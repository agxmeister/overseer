'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import Task, {ScaleDirection} from "@/components/Task/Task";
import React, {useState} from "react";
import Slot from "@/components/Slot/Slot";
import {getDates} from "@/utils/date";

type Issue = {
    key: string,
    estimatedStartDate: string,
    estimatedFinishDate: string,
    summary: string,
}

export default function Page()
{
    const [scale, setScale] = useState<number>(1);
    const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (['=', '+'].includes(event.key)) {
            setScale(scale + 0.1);
        } else if (['-', '_'].includes(event.key)) {
            setScale(scale - 0.1);
        }
    };

    const dates = getDates(new Date("2023-07-25"), new Date("2023-09-05"));

    const [scaleTaskId, setScaleTaskId] = useState<string|null>(null);
    const onScale = (taskId: string) => {
        setScaleTaskId(taskId);
    }

    const {data, mutate} = useSWR('http://localhost:8080/api/v1/tasks', (api: string) => fetch(api).then(res => res.json()));
    const onMutate = (fetcher: Function, mutation: {taskId: string, direction: string, date: string}) => {
        const optimisticData = data.map((issue: Issue) =>
            issue.key === mutation.taskId ?
                (mutation.direction === ScaleDirection.Left ?
                    {...issue, estimatedStartDate: mutation.date} :
                    {...issue, estimatedFinishDate: mutation.date}) :
                issue);
        mutate(fetcher,{
            optimisticData: optimisticData,
            populateCache: (mutatedIssue, issues) => {
                return issues.map((issue: Issue) => issue.key === mutatedIssue.key ? mutatedIssue : issue);
            },
            revalidate: false
        });
    }

    const tasks = data ? data.map((issue: Issue) =>
        <Task
            key={issue.key}
            id={issue.key}
            start={issue.estimatedStartDate}
            finish={issue.estimatedFinishDate}
            card={<Card
                key={issue.key}
                id={issue.key}
                title={issue.summary}
            />}
            onScale={onScale}
        />
    ): [];

    const slots = scaleTaskId !== null ? dates
        .map(date => <Slot key={date} id={scaleTaskId} position={date} onMutate={onMutate}/>) : [];

    return (
        <div tabIndex={0} onKeyDown={handleKeyDown}>
            <Map scale={scale} dates={dates} tasks={tasks} slots={slots}/>
        </div>
    );
}
