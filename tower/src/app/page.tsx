'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";
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

export default function Page()
{
    const dates = getDates(new Date("2023-07-25"), new Date("2023-08-05"));

    const [scaleTaskId, setScaleTaskId] = useState<string|null>(null);
    const onScale = (taskId: string) => {
        setScaleTaskId(taskId);
    }

    const {data, mutate} = useSWR('http://localhost:8080/api/v1/tasks', (api: string) => fetch(api).then(res => res.json()));
    const onMutate = (fetcher: Function, mutation: {taskId: string, startDate: string}) => {
        mutate(fetcher,{
            optimisticData: data.map((issue: Issue) => issue.key === mutation.taskId ? {...issue, estimatedStartDate: mutation.startDate} : issue),
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
                start={issue.estimatedStartDate}
                finish={issue.estimatedFinishDate}
                title={issue.summary}
            />}
            onScale={onScale}
        />
    ): [];

    const slots = scaleTaskId !== null ? dates
        .map(date => <Slot key={date} id={scaleTaskId} position={date} onMutate={onMutate}/>) : [];

    return <Map dates={dates} tasks={tasks} slots={slots}/>
}
