import pandas as pd
import matplotlib.pyplot as plt

# class accuracy
df = pd.read_csv(r'master.csv')
df = df.groupby(by=['patient', 'student'])['trial correct'].agg(Sum = 'sum')
print(df)
a = df[df['Sum'] > 0.0]
aa = a.shape[0]
print("non zeroes?", aa)
b= df[df['Sum'] == 0.0]
bb = b.shape[0]
print("zeroes?", bb)
c = aa/(aa+bb) * 100
print('class accuracy: {:0.2f}%'.format(c))

print('\n')

#accuracy by patient scenario, aka Scenario Performance by Class
df = pd.read_csv(r'master.csv')
all_pts = set(df['patient'])
print('scenario performance')
scenario_xcoord = list(range(1,len(all_pts)+1))
scenario_y = []
scenario_ticks = []
for pt in all_pts:
    mini = df[df['patient'] == pt]
    mini = mini.groupby(by=['student'])['trial correct'].agg(Sum = 'sum')
    a = mini[mini['Sum'] > 0.0]
    aa = a.shape[0]
    b = mini[mini['Sum'] == 0.0]
    bb = b.shape[0]
    c = aa/(aa+bb) * 100
    scenario_y.append(c)
    scenario_ticks.append(pt)
    print('patient {}: {:0.2f}%'.format(pt,c))


print('\n')

plt.bar(scenario_xcoord, scenario_y, tick_label = scenario_ticks, 
        width = 0.7, color = ['green']) 
plt.title('Scenario Performance by Class')   
plt.ylabel('Percent Correct') 
plt.xlabel('Patient Scenario') 
plt.show() 

print('\n')

exit(-1);

#accuracy by medication group within each scenario
df = pd.read_csv(r'master.csv')
all_pts = set(df['patient'])
print('performance by medication group within each scenario')
group_y = []
group_ticks = []
for pt in all_pts:
    print('pt case: ',pt)
    mini = df[df['patient'] == pt]
    all_groups = set(mini['group'])
    group_xcoord = list(range(1,len(all_groups)+1))
    for group in all_groups:
        group_ticks.append(group)
        mini_2 = mini[mini['group'] == group]
        mini_2 = mini_2.groupby(by=['group','student'])['group correct'].agg(Sum = 'sum', Count='count')  
        a = mini_2[mini_2['Sum'] > 0.0]  
        aa = a.shape[0]
        b = mini_2[mini_2['Sum'] == 0.0]
        bb = b.shape[0]
        c = aa/(aa+bb) * 100
        group_y.append(c)
        print('medication {}: {:0.2f}%'.format(group,c))
    plt.title('Medication Group Performance for Scenario {}'.format(pt))   
    plt.ylabel('Percent Correct') 
    plt.xlabel('Medication Groups') 
    plt.bar(group_xcoord, group_y, tick_label = group_ticks, 
        width = 0.7, color = ['green']) 
    plt.show()
    group_y = []
    group_ticks = []
    group_xcoord = []

print('\n')

#accuracy by student
df = pd.read_csv(r'master.csv')
all_stdns = set(df['student'])
print("student's overall performance")
for stdn in all_stdns:
    mini_3 = df[df['student'] == stdn]
    mini_3 = mini_3.groupby(by=['patient'])['trial correct'].agg(Sum = 'sum')
    a = mini_3[mini_3['Sum'] > 0.0]
    aa = a.shape[0]
    b = mini_3[mini_3['Sum'] == 0.0]
    bb = b.shape[0]
    c = aa/(aa+bb) * 100
    print('{}: {:0.2f}% of patients'.format(stdn,c))